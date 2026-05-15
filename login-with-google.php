<?php
/**
 * Plugin Name: Login with Google
 * Plugin URI: https://github.com/appcoderikbal/Login-with-google-wp-plugin
 * Description: Allow users to login or register on your WordPress site using their Google account. Provides a shortcode [login_with_google] to display the button.
 * Version: 1.0.1
 * Author: Ikbal Singh
 * Author URI: https://github.com/appcoderikbal
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: login-with-google
 * GitHub Plugin URI: https://github.com/appcoderikbal/Login-with-google-wp-plugin
 * GitHub Repo: appcoderikbal/Login-with-google-wp-plugin
 * Primary Branch: main
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LWG_VERSION', '1.0.1');
define('LWG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LWG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LWG_PLUGIN_FILE', __FILE__);
define('LWG_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once LWG_PLUGIN_DIR . 'includes/class-lwg-admin.php';
require_once LWG_PLUGIN_DIR . 'includes/class-lwg-google-auth.php';
require_once LWG_PLUGIN_DIR . 'includes/class-lwg-shortcode.php';
require_once LWG_PLUGIN_DIR . 'includes/class-lwg-updater.php';

/**
 * Main Plugin Class
 */
class Login_With_Google
{

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Initialize admin settings
        if (is_admin()) {
            new LWG_Admin();
            new LWG_Updater();
        }

        // Initialize Google Auth handler
        new LWG_Google_Auth();

        // Initialize shortcode
        new LWG_Shortcode();

        // Enqueue frontend styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

        // Add login form button
        add_action('login_form', array($this, 'add_login_button'));
        add_action('register_form', array($this, 'add_login_button'));

        // Plugin activation hook
        register_activation_hook(LWG_PLUGIN_FILE, array($this, 'activate'));
    }

    /**
     * Enqueue frontend styles and scripts
     */
    public function enqueue_styles()
    {
        wp_enqueue_style(
            'lwg-styles',
            LWG_PLUGIN_URL . 'assets/css/lwg-style.css',
            array(),
            LWG_VERSION
        );
    }

    /**
     * Add Google login button to WP login/register forms
     */
    public function add_login_button()
    {
        $options = get_option('lwg_settings', array());
        $show_on_login = isset($options['show_on_login_page']) ? $options['show_on_login_page'] : 1;

        if ($show_on_login) {
            // Enqueue styles on login page
            wp_enqueue_style(
                'lwg-styles',
                LWG_PLUGIN_URL . 'assets/css/lwg-style.css',
                array(),
                LWG_VERSION
            );
            echo '<div class="lwg-login-form-wrapper">';
            echo '<div class="lwg-divider"><span>' . esc_html__('OR', 'login-with-google') . '</span></div>';
            echo do_shortcode('[login_with_google]');
            echo '</div>';
        }
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Set default options
        $defaults = array(
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => home_url('/wp-json/lwg/v1/callback'),
            'show_on_login_page' => 1,
            'button_text' => 'Sign in with Google',
            'redirect_after_login' => home_url(),
        );
        if (!get_option('lwg_settings')) {
            add_option('lwg_settings', $defaults);
        }
    }
}

// Initialize the plugin
Login_With_Google::get_instance();
