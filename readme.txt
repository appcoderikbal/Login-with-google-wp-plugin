=== Login with Google ===
Contributors: appcoderikbal
Tags: google login, social login, google oauth, sign in with google, google register
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Allow users to login or register on your WordPress site using their Google account.

== Description ==

**Login with Google** is a lightweight WordPress plugin that enables users to sign in or register using their Google account. It provides a seamless one-click authentication experience.

= Features =

* **One-Click Login/Register** – Users can log in or create an account with a single click using their Google account.
* **Smart User Handling** – Automatically logs in existing users or creates new accounts for first-time visitors.
* **Shortcode Support** – Use `[login_with_google]` to place the login button anywhere on your site.
* **Admin Settings Panel** – Easy-to-use settings page to configure Google OAuth credentials.
* **Customizable Button Text** – Change the button label from the admin panel.
* **Role Assignment** – Choose the default role for new users who register via Google.
* **Redirect Control** – Set a custom URL to redirect users after login.
* **Login Page Integration** – Optionally display the button on the default WordPress login page.
* **CSRF Protection** – Built-in state verification for secure authentication.
* **Auto-Updates** – Receives updates directly from GitHub through the WordPress dashboard.
* **Dark Mode Support** – Button automatically adapts to dark color schemes.

= Shortcode Usage =

Basic usage:
`[login_with_google]`

With custom text:
`[login_with_google text="Continue with Google"]`

With custom CSS class:
`[login_with_google class="my-custom-class"]`

= Setup Guide =

1. Go to [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Create a new OAuth 2.0 Client ID (Web Application)
3. Add the Redirect URI from the plugin settings to "Authorized redirect URIs"
4. Copy the Client ID and Client Secret
5. Go to WordPress Admin → Settings → Login with Google
6. Paste your credentials and save

== Installation ==

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate the plugin
4. Go to Settings → Login with Google to configure

== Frequently Asked Questions ==

= Do I need a Google Cloud project? =
Yes, you need to create a project in Google Cloud Console and set up OAuth 2.0 credentials.

= Does this plugin create new users? =
Yes, if a user logs in with Google and their email doesn't exist in WordPress, a new account is created automatically (if registration is enabled in WordPress Settings).

= Can I customize the button appearance? =
Yes, you can change the button text from the settings page, and use custom CSS classes via the shortcode.

= Is it secure? =
Yes, the plugin uses OAuth 2.0 with CSRF protection via WordPress nonces and state verification.

== Changelog ==

= 1.0.1 =
* Fix: Allow Google sign-ups even when WordPress general registration is disabled
* Google OAuth now bypasses the "Anyone can register" setting

= 1.0.0 =
* Initial release
* Google OAuth 2.0 login and registration
* Admin settings page
* Shortcode support
* GitHub auto-update support
* Dark mode button styles
