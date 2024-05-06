<?php
/**
 * Function to handle form submission and send magic login link
 */
function magic_login_form()
{
    if (isset($_POST['user_email'])) {
        $email = sanitize_email($_POST['user_email']);
        $user = get_user_by('email', $email);

        if ($user) {
            $token = wp_generate_password(16, false, false);

            // Save the token and time in user meta for later verification
            update_user_meta($user->ID, 'magic_login_token', $token);
            update_user_meta($user->ID, 'magic_login_token_time', time());

            // Send the magic login link via email
            send_magic_login_link($email, $token);
            wp_send_json_success();
        } else {
            // Send error response if user not found
            wp_send_json_error('<p>User not found.</p>');
        }
    }
}
add_action('wp_ajax_magic_login_form', 'magic_login_form');
add_action('wp_ajax_nopriv_magic_login_form', 'magic_login_form');

/**
 * Function to get magic login token time
 */
function get_magic_login_token_time($email)
{
    $user = get_user_by('email', $email);
    if ($user) {
        return get_user_meta($user->ID, 'magic_login_token_time', true);
    }
    return false;
}

/**
 * Function to verify magic login token
 */
function verify_magic_login_token($email, $token)
{
    $user = get_user_by('email', $email);
    if ($user) {
        $stored_token = get_user_meta($user->ID, 'magic_login_token', true);
        return ($stored_token === $token);
    }
    return false;
}

/**
 * Function to redirect user to home
 */
function magic_link_expire_redirect()
{
    echo '<script>setTimeout(function() {window.location.href = "' . home_url('/') . '";}, 5000);</script>';
}

/**
 * Function to handle magic login
 */
function handle_magic_login()
{
    if (isset($_GET['user_mail']) && isset($_GET['token'])) {
        $email = sanitize_email($_GET['user_mail']);
        $token = sanitize_text_field($_GET['token']);

        // Check if token and email match
        if (verify_magic_login_token($email, $token)) {
            // Check if token is still valid (within 15 minutes)
            $token_time = get_magic_login_token_time($email);
            $expiration_time = 15 * MINUTE_IN_SECONDS;
            if ($token_time && (time() - $token_time <= $expiration_time)) {
                // Token is valid, log in the user
                $user = get_user_by('email', $email);
                if ($user) {
                    wp_set_current_user($user->ID, $user->user_login);
                    wp_set_auth_cookie($user->ID);
                    do_action('wp_login', $user->user_login, $user);
                    // Redirect user to home page or any desired page after login
                    wp_redirect(home_url('/page')); // Replace 'page' with your desired page slug
                    exit;
                }
            } else {
                // Token has expired
                $user = get_user_by('email', $email);
                if ($user) {
                    delete_user_meta($user->ID, 'magic_login_token');
                    delete_user_meta($user->ID, 'magic_login_token_time');
                }
                magic_link_expire_redirect();
                wp_die('Magic login link has expired. You will be redirected to the home page in 5 seconds.');
            }
        } else {
            // Token or email is invalid
            magic_link_expire_redirect();
            wp_die('Invalid magic login link. You will be redirected to the home page in 5 seconds.');
        }
    }
}
add_action('init', 'handle_magic_login');

/**
 * Function to send magic login link via email
 */
function send_magic_login_link($email, $token)
{
    $user = get_user_by('email', $email);
    $first_name = '';
    $last_name = '';
    if ($user) {
        $first_name = $user->first_name;
        $last_name = $user->last_name;
    }

    $login_link = home_url("?user_mail=$email&token=$token");
    $message = "Hello $first_name $last_name,\n\n";
    $message .= "Click on the following link to login into xyz website: $login_link\n";
    $message .= "This link will expire in 15 minutes and can only be used once.\n\n";
    $message .= "Best regards,\n xyz company";

    // Send the email
    wp_mail($email, 'xyz Website Login Link', $message);
}

/**
 * Shortcode for magic login form
 */
function magic_login_form_shortcode()
{
    ob_start();
?>
    <form id="custom-profile-form" method="post" action="" class="LoginLink">
        <div class="LoginLinkFields">
            <fieldset>
                <input type="email" name="user_email" id="user_email" placeholder="<?php _e('Enter Your Email', '[TEXT_DOMAIN]'); ?>" required>
            </fieldset>
        </div>
        <div>
            <input type="submit" name="login_link" class="LoginLinkBtn" value="<?php _e('Send Login Link', '[TEXT_DOMAIN]'); ?>"> <!-- Replace '[TEXT_DOMAIN]' with your text domain -->
        </div>
    </form>
    <div id="login-message"></div>
<?php
    return ob_get_clean();
}
add_shortcode('magic_login_form', 'magic_login_form_shortcode');
