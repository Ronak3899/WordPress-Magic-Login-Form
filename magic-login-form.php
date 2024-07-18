<?php
// Add this to your theme's functions.php or in a custom plugin file

// Function to handle form submission and send magic login link
function magic_login_form() {
    check_ajax_referer('magic-login-nonce', 'security');

    if (!isset($_POST['member_mail'])) {
        wp_send_json_error('Email not provided.');
    }

    $email = sanitize_email($_POST['member_mail']);
    $user = get_user_by('email', $email);

    if (!$user) {
        wp_send_json_error('User not found.');
    }

    if (!in_array('member', (array) $user->roles)) {
        wp_send_json_error('User does not have the member role.');
    }

    $token = wp_generate_password(32, false);
    $expiration = time() + (15 * MINUTE_IN_SECONDS);

    update_user_meta($user->ID, 'magic_login_token', $token);
    update_user_meta($user->ID, 'magic_login_token_expiration', $expiration);

    $sent = send_magic_login_link($email, $token);

    if ($sent) {
        wp_send_json_success('Magic login link sent successfully.');
    } else {
        wp_send_json_error('Failed to send magic login link.');
    }
}
add_action('wp_ajax_nopriv_magic_login_form', 'magic_login_form');
add_action('wp_ajax_magic_login_form', 'magic_login_form');

// Function to send magic login link via email
function send_magic_login_link($email, $token) {
    $user = get_user_by('email', $email);
    $login_link = add_query_arg(
        array('action' => 'magic_login', 'email' => $email, 'token' => $token),
        home_url()
    );

    $subject = 'Your Magic Login Link for ' . get_bloginfo('name');
    $message = "Hello {$user->display_name},\n\n";
    $message .= "Click the following link to log in:\n\n";
    $message .= $login_link . "\n\n";
    $message .= "This link will expire in 15 minutes and can only be used once.\n\n";
    $message .= "Best regards,\n" . get_bloginfo('name');

    return wp_mail($email, $subject, $message);
}

// Function to handle magic login
function handle_magic_login() {
    if (isset($_GET['action']) && $_GET['action'] === 'magic_login' && isset($_GET['email']) && isset($_GET['token'])) {
        $email = sanitize_email($_GET['email']);
        $token = sanitize_text_field($_GET['token']);

        $user = get_user_by('email', $email);

        if (!$user) {
            wp_die('Invalid user');
        }

        $stored_token = get_user_meta($user->ID, 'magic_login_token', true);
        $expiration = get_user_meta($user->ID, 'magic_login_token_expiration', true);

        if ($token !== $stored_token || time() > $expiration) {
            wp_die('Invalid or expired login link');
        }

        // Login successful
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        do_action('wp_login', $user->user_login, $user);

        // Clean up
        delete_user_meta($user->ID, 'magic_login_token');
        delete_user_meta($user->ID, 'magic_login_token_expiration');

        wp_safe_redirect(home_url());
        exit;
    }
}
add_action('init', 'handle_magic_login');

// Shortcode to display magic login form
function magic_login_form_shortcode() {
    ob_start();
    ?>
    <form id="magic-login-form">
        <?php wp_nonce_field('magic-login-nonce', 'security'); ?>
        <input type="email" id="member" name="member" required placeholder="Enter your email">
        <button type="submit">Send Magic Login Link</button>
    </form>
    <div id="login-message"></div>
    <script>
    jQuery(document).ready(function($) {
        $('#magic-login-form').on('submit', function(e) {
            e.preventDefault();

            var member = $("#member").val().trim();
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (member === "" || !emailRegex.test(member)) {
                $("#login-message").addClass("LoginMessageError");
                $("#login-message").html("<p>Please enter a valid email address.</p>").css({ color: "red", "padding-bottom": "20px" });
                return;
            }

            var data = {
                action: "magic_login_form",
                member_mail: member,
                security: $("input[name=security]").val()
            };

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: "POST",
                data: data,
                success: function(response) {
                    if (response.success) {
                        $('#login-message').text(response.data).css('color', 'green');
                    } else {
                        $('#login-message').text(response.data).css('color', 'red');
                    }
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('magic_login', 'magic_login_form_shortcode');
?>
