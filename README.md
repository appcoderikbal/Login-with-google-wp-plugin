# Login with Google - WordPress Plugin

Allow users to login or register on your WordPress site using their Google account.

## Features

- ✅ **One-Click Login/Register** – Google OAuth 2.0 authentication
- ✅ **Smart User Handling** – Auto-login for existing users, auto-register for new ones
- ✅ **Shortcode** – `[login_with_google]` - place the button anywhere
- ✅ **Admin Settings** – Easy configuration panel under Settings menu
- ✅ **Customizable** – Button text, redirect URL, user role
- ✅ **Secure** – CSRF protection with state verification
- ✅ **Auto-Updates** – Updates via WordPress dashboard from GitHub releases
- ✅ **Dark Mode** – Automatic dark theme support

## Installation

1. Download or clone this repository
2. Upload to `/wp-content/plugins/login-with-google/`
3. Activate the plugin in WordPress
4. Go to **Settings → Login with Google**
5. Enter your Google OAuth credentials

## Google Cloud Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Create a new project (or select existing)
3. Go to **APIs & Services → Credentials**
4. Click **Create Credentials → OAuth 2.0 Client ID**
5. Select **Web Application**
6. Add **Authorized redirect URI**: `https://yoursite.com/wp-json/lwg/v1/callback`
7. Copy **Client ID** and **Client Secret** into plugin settings

## Shortcode

```
[login_with_google]
[login_with_google text="Continue with Google"]
[login_with_google class="my-class"]
```

## Versioning

This plugin supports automatic updates from GitHub. To release a new version:

1. Update the version number in `login-with-google.php` (both the header and `LWG_VERSION` constant)
2. Update `readme.txt` stable tag
3. Commit and push changes
4. Create a new GitHub release/tag (e.g., `v1.0.1`)
5. WordPress sites using this plugin will detect the update automatically

## File Structure

```
login-with-google/
├── login-with-google.php          # Main plugin file
├── readme.txt                     # WordPress readme
├── README.md                      # GitHub readme
├── includes/
│   ├── class-lwg-admin.php        # Admin settings page
│   ├── class-lwg-google-auth.php  # Google OAuth handler
│   ├── class-lwg-shortcode.php    # Shortcode renderer
│   └── class-lwg-updater.php      # GitHub auto-updater
└── assets/
    └── css/
        ├── lwg-style.css          # Frontend styles
        └── lwg-admin.css          # Admin styles
```

## License

GPL v2 or later
