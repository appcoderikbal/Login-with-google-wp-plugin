<?php
/**
 * Admin Settings Page
 *
 * @package Login_With_Google
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LWG_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_filter( 'plugin_action_links_' . LWG_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Google Login by Ikbal', 'login-with-google' ),
            __( 'Google Login by Ikbal', 'login-with-google' ),
            'manage_options',
            'login-with-google',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Add settings link to plugin action links
     */
    public function add_settings_link( $links ) {
        $settings_link = '<a href="' . admin_url( 'options-general.php?page=login-with-google' ) . '">' . __( 'Settings', 'login-with-google' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles( $hook ) {
        if ( 'settings_page_login-with-google' !== $hook ) {
            return;
        }
        wp_enqueue_style(
            'lwg-admin-styles',
            LWG_PLUGIN_URL . 'assets/css/lwg-admin.css',
            array(),
            LWG_VERSION
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting( 'lwg_settings_group', 'lwg_settings', array( $this, 'sanitize_settings' ) );

        // Google API Section
        add_settings_section(
            'lwg_google_api_section',
            __( 'Google API Configuration', 'login-with-google' ),
            array( $this, 'render_api_section' ),
            'login-with-google'
        );

        add_settings_field(
            'client_id',
            __( 'Client ID', 'login-with-google' ),
            array( $this, 'render_text_field' ),
            'login-with-google',
            'lwg_google_api_section',
            array( 'field' => 'client_id', 'description' => 'Enter your Google OAuth 2.0 Client ID' )
        );

        add_settings_field(
            'client_secret',
            __( 'Client Secret', 'login-with-google' ),
            array( $this, 'render_password_field' ),
            'login-with-google',
            'lwg_google_api_section',
            array( 'field' => 'client_secret', 'description' => 'Enter your Google OAuth 2.0 Client Secret' )
        );

        add_settings_field(
            'redirect_uri',
            __( 'Redirect URI', 'login-with-google' ),
            array( $this, 'render_readonly_field' ),
            'login-with-google',
            'lwg_google_api_section',
            array( 'field' => 'redirect_uri', 'description' => 'Copy this URL and add it to your Google Cloud Console → Authorized redirect URIs' )
        );

        // Button Settings Section
        add_settings_section(
            'lwg_button_section',
            __( 'Button Settings', 'login-with-google' ),
            null,
            'login-with-google'
        );

        add_settings_field(
            'button_text',
            __( 'Button Text', 'login-with-google' ),
            array( $this, 'render_text_field' ),
            'login-with-google',
            'lwg_button_section',
            array( 'field' => 'button_text', 'description' => 'Text displayed on the Google login button' )
        );

        add_settings_field(
            'show_on_login_page',
            __( 'Show on Login Page', 'login-with-google' ),
            array( $this, 'render_checkbox_field' ),
            'login-with-google',
            'lwg_button_section',
            array( 'field' => 'show_on_login_page', 'description' => 'Display the Google login button on the WordPress login/register page' )
        );

        // Redirect Settings Section
        add_settings_section(
            'lwg_redirect_section',
            __( 'Redirect Settings', 'login-with-google' ),
            null,
            'login-with-google'
        );

        add_settings_field(
            'redirect_after_login',
            __( 'Redirect After Login', 'login-with-google' ),
            array( $this, 'render_text_field' ),
            'login-with-google',
            'lwg_redirect_section',
            array( 'field' => 'redirect_after_login', 'description' => 'URL to redirect users after successful login (default: homepage)' )
        );

        add_settings_field(
            'default_role',
            __( 'Default User Role', 'login-with-google' ),
            array( $this, 'render_role_dropdown' ),
            'login-with-google',
            'lwg_redirect_section',
            array( 'field' => 'default_role', 'description' => 'Role assigned to new users who register via Google' )
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();
        $sanitized['client_id']           = sanitize_text_field( $input['client_id'] ?? '' );
        $sanitized['client_secret']       = sanitize_text_field( $input['client_secret'] ?? '' );
        $sanitized['redirect_uri']        = esc_url_raw( home_url( '/?lwg_callback=1' ) );
        $sanitized['button_text']         = sanitize_text_field( $input['button_text'] ?? 'Sign in with Google' );
        $sanitized['show_on_login_page']  = isset( $input['show_on_login_page'] ) ? 1 : 0;
        $sanitized['redirect_after_login']= esc_url_raw( $input['redirect_after_login'] ?? home_url() );
        $sanitized['default_role']        = sanitize_text_field( $input['default_role'] ?? 'subscriber' );
        return $sanitized;
    }

    /**
     * Render the API section description
     */
    public function render_api_section() {
        echo '<p class="lwg-section-desc">';
        echo wp_kses(
            sprintf(
                __( 'Configure your Google OAuth 2.0 credentials. You can create them in the %sGoogle Cloud Console%s.', 'login-with-google' ),
                '<a href="https://console.cloud.google.com/apis/credentials" target="_blank">',
                '</a>'
            ),
            array( 'a' => array( 'href' => array(), 'target' => array() ) )
        );
        echo '</p>';
        echo '<div class="lwg-setup-steps">';
        echo '<h4>' . esc_html__( 'Quick Setup Guide:', 'login-with-google' ) . '</h4>';
        echo '<ol>';
        echo '<li>' . esc_html__( 'Go to Google Cloud Console → APIs & Services → Credentials', 'login-with-google' ) . '</li>';
        echo '<li>' . esc_html__( 'Create OAuth 2.0 Client ID (Web Application type)', 'login-with-google' ) . '</li>';
        echo '<li>' . esc_html__( 'Add the Redirect URI shown below to Authorized redirect URIs', 'login-with-google' ) . '</li>';
        echo '<li>' . esc_html__( 'Copy Client ID and Client Secret and paste below', 'login-with-google' ) . '</li>';
        echo '</ol>';
        echo '</div>';
    }

    /**
     * Render text input field
     */
    public function render_text_field( $args ) {
        $options = get_option( 'lwg_settings', array() );
        $value = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : '';
        printf(
            '<input type="text" id="lwg_%s" name="lwg_settings[%s]" value="%s" class="regular-text lwg-input" />',
            esc_attr( $args['field'] ),
            esc_attr( $args['field'] ),
            esc_attr( $value )
        );
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    /**
     * Render password input field
     */
    public function render_password_field( $args ) {
        $options = get_option( 'lwg_settings', array() );
        $value = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : '';
        printf(
            '<div class="lwg-password-wrapper"><input type="password" id="lwg_%s" name="lwg_settings[%s]" value="%s" class="regular-text lwg-input" /><button type="button" class="button lwg-toggle-password" onclick="var f=this.previousElementSibling;f.type=f.type===\'password\'?\'text\':\'password\';this.textContent=f.type===\'password\'?\'Show\':\'Hide\';">Show</button></div>',
            esc_attr( $args['field'] ),
            esc_attr( $args['field'] ),
            esc_attr( $value )
        );
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    /**
     * Render readonly field
     */
    public function render_readonly_field( $args ) {
        $value = home_url( '/?lwg_callback=1' );
        printf(
            '<input type="text" id="lwg_%s" value="%s" class="regular-text lwg-input lwg-readonly" readonly onclick="this.select();document.execCommand(\'copy\');"/>',
            esc_attr( $args['field'] ),
            esc_attr( $value )
        );
        echo '<button type="button" class="button lwg-copy-btn" onclick="var f=document.getElementById(\'lwg_redirect_uri\');f.select();document.execCommand(\'copy\');this.textContent=\'Copied!\';setTimeout(function(){document.querySelector(\'.lwg-copy-btn\').textContent=\'Copy\';},2000);">Copy</button>';
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    /**
     * Render checkbox field
     */
    public function render_checkbox_field( $args ) {
        $options = get_option( 'lwg_settings', array() );
        $checked = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : 0;
        printf(
            '<label class="lwg-toggle"><input type="checkbox" id="lwg_%s" name="lwg_settings[%s]" value="1" %s /><span class="lwg-toggle-slider"></span></label>',
            esc_attr( $args['field'] ),
            esc_attr( $args['field'] ),
            checked( $checked, 1, false )
        );
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    /**
     * Render role dropdown field
     */
    public function render_role_dropdown( $args ) {
        $options = get_option( 'lwg_settings', array() );
        $selected = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : 'subscriber';
        $roles = wp_roles()->get_names();

        echo '<select id="lwg_' . esc_attr( $args['field'] ) . '" name="lwg_settings[' . esc_attr( $args['field'] ) . ']" class="lwg-input">';
        foreach ( $roles as $role_value => $role_name ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $role_value ),
                selected( $selected, $role_value, false ),
                esc_html( $role_name )
            );
        }
        echo '</select>';
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap lwg-settings-wrap">
            <div class="lwg-header">
                <div class="lwg-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40">
                        <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
                        <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
                        <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
                        <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
                    </svg>
                </div>
                <div class="lwg-header-text">
                    <h1><?php echo esc_html__( 'Google Login by Ikbal', 'login-with-google' ); ?></h1>
                    <p class="lwg-version">v<?php echo esc_html( LWG_VERSION ); ?></p>
                </div>
            </div>

            <div class="lwg-content">
                <div class="lwg-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'lwg_settings_group' );
                        do_settings_sections( 'login-with-google' );
                        submit_button( __( 'Save Settings', 'login-with-google' ), 'primary lwg-save-btn' );
                        ?>
                    </form>
                </div>

                <div class="lwg-sidebar">
                    <div class="lwg-card">
                        <h3><?php echo esc_html__( 'Shortcode', 'login-with-google' ); ?></h3>
                        <p><?php echo esc_html__( 'Use this shortcode to display the Google login button anywhere:', 'login-with-google' ); ?></p>
                        <code class="lwg-shortcode-display">[login_with_google]</code>
                    </div>

                    <div class="lwg-card">
                        <h3><?php echo esc_html__( 'Status', 'login-with-google' ); ?></h3>
                        <?php $this->render_status_card(); ?>
                    </div>

                    <div class="lwg-card">
                        <h3><?php echo esc_html__( 'Support', 'login-with-google' ); ?></h3>
                        <p><?php echo esc_html__( 'Need help? Visit our GitHub repository for documentation and support.', 'login-with-google' ); ?></p>
                        <a href="https://github.com/appcoderikbal/Login-with-google-wp-plugin" target="_blank" class="button"><?php echo esc_html__( 'Visit GitHub', 'login-with-google' ); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render status card
     */
    private function render_status_card() {
        $options = get_option( 'lwg_settings', array() );
        $client_id = ! empty( $options['client_id'] );
        $client_secret = ! empty( $options['client_secret'] );
        $configured = $client_id && $client_secret;

        echo '<ul class="lwg-status-list">';
        printf(
            '<li class="%s">%s %s</li>',
            $client_id ? 'lwg-status-ok' : 'lwg-status-error',
            $client_id ? '✅' : '❌',
            esc_html__( 'Client ID', 'login-with-google' )
        );
        printf(
            '<li class="%s">%s %s</li>',
            $client_secret ? 'lwg-status-ok' : 'lwg-status-error',
            $client_secret ? '✅' : '❌',
            esc_html__( 'Client Secret', 'login-with-google' )
        );
        printf(
            '<li class="%s">%s %s</li>',
            $configured ? 'lwg-status-ok' : 'lwg-status-error',
            $configured ? '✅' : '❌',
            esc_html__( 'Ready to use', 'login-with-google' )
        );
        echo '</ul>';
    }
}
