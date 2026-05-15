<?php
/**
 * Shortcode Handler
 *
 * @package Login_With_Google
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LWG_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode( 'login_with_google', array( $this, 'render_button' ) );

        // Display error messages on login page
        add_action( 'login_message', array( $this, 'display_error_message' ) );
    }

    /**
     * Render the Google login button
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_button( $atts = array() ) {
        // If user is already logged in, don't show the button
        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $avatar = get_user_meta( $current_user->ID, 'lwg_google_avatar', true );
            $is_google = get_user_meta( $current_user->ID, 'lwg_google_linked', true );

            if ( $is_google ) {
                $output = '<div class="lwg-logged-in">';
                if ( $avatar ) {
                    $output .= '<img src="' . esc_url( $avatar ) . '" alt="' . esc_attr( $current_user->display_name ) . '" class="lwg-user-avatar" />';
                }
                $output .= '<span class="lwg-user-name">' . esc_html( sprintf( __( 'Logged in as %s', 'login-with-google' ), $current_user->display_name ) ) . '</span>';
                $output .= '<a href="' . esc_url( wp_logout_url( home_url() ) ) . '" class="lwg-logout-link">' . esc_html__( 'Logout', 'login-with-google' ) . '</a>';
                $output .= '</div>';
                return $output;
            }
            return '';
        }

        $options = get_option( 'lwg_settings', array() );

        // Check if plugin is configured
        if ( empty( $options['client_id'] ) || empty( $options['client_secret'] ) ) {
            if ( current_user_can( 'manage_options' ) ) {
                return '<div class="lwg-notice lwg-notice-warning">' . 
                    esc_html__( 'Login with Google: Please configure the plugin settings.', 'login-with-google' ) . 
                    ' <a href="' . admin_url( 'options-general.php?page=login-with-google' ) . '">' . esc_html__( 'Go to Settings', 'login-with-google' ) . '</a></div>';
            }
            return '';
        }

        $atts = shortcode_atts( array(
            'text'  => '',
            'class' => '',
        ), $atts, 'login_with_google' );

        $button_text = ! empty( $atts['text'] ) ? $atts['text'] : ( ! empty( $options['button_text'] ) ? $options['button_text'] : __( 'Sign in with Google', 'login-with-google' ) );
        $extra_class = ! empty( $atts['class'] ) ? ' ' . sanitize_html_class( $atts['class'] ) : '';

        // Build auth URL
        $auth = new LWG_Google_Auth();
        $auth_url = $auth->get_auth_url();

        $output = '<div class="lwg-button-wrapper' . esc_attr( $extra_class ) . '">';
        $output .= '<a href="' . esc_url( $auth_url ) . '" class="lwg-google-btn" id="lwg-google-login-btn">';
        $output .= '<span class="lwg-google-icon">';
        $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20">';
        $output .= '<path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>';
        $output .= '<path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>';
        $output .= '<path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>';
        $output .= '<path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>';
        $output .= '</svg>';
        $output .= '</span>';
        $output .= '<span class="lwg-btn-text">' . esc_html( $button_text ) . '</span>';
        $output .= '</a>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Display error messages on the login page
     */
    public function display_error_message( $message ) {
        if ( isset( $_GET['lwg_error'] ) && ! empty( $_GET['lwg_error'] ) ) {
            $error = urldecode( sanitize_text_field( $_GET['lwg_error'] ) );
            $message .= '<div id="login_error" class="notice notice-error"><p><strong>' . esc_html__( 'Login with Google Error:', 'login-with-google' ) . '</strong> ' . esc_html( $error ) . '</p></div>';
        }
        return $message;
    }
}
