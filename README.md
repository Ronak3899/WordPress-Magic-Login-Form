# WordPress Magic Login Function
This function allows users to log in to the website using a magic login link sent to their email address. This code can be added to your theme's functions.php file.

## Usage
1. Copy the provided PHP code and paste it into the functions.php file of your WordPress theme.
2. Use the shortcode [magic_login_form] to display the magic login form on any page or post.

## Installation
1. Navigate to the functions.php file of your WordPress theme.
2. Paste the provided PHP code at the end of the file.
3. Save the changes.

## Usage
1. Insert the magic login form shortcode [magic_login_form] into any post or page where you want the form to appear.
2. Users enter their email address and submit the form.
3. If the email address is registered, a magic login link will be sent to the user's email.
4. The user clicks on the link to log in automatically.

## Customization

1. You can customize the email message and login link expiration time directly in the functions.php file.

## Functionality
1. The function sends a unique magic login link to the user's email address.
2. The link expires after 15 minutes and can only be used once.
3. Users are redirected to the specified page after successful login.
4. If the link expires or is invalid, users are redirected to the home page.

## Shortcode
[magic_login_form]
