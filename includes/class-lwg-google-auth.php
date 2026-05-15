<?php
/**
 * Google OAuth Authentication Handler
 *
 * @package Login_With_Google
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LWG_Google_Auth {

    /**
     * Google OAuth endpoints
     */
    const GOOGLE_AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const GOOGLE_USER_URL  = 'https://www.googleapis.com/oauth2/v2/userinfo';

    /**
     * Constructor
     */
    public function __construct() {
        // Register REST API callback endpoint
        add_action( 'rest_api_init', array( $this, 'register_callback_route' ) );

        // Register the auth initiation endpoint
        add_action( 'init', array( $this, 'handle_auth_redirect' ) );
    }

    /**
     * Register the callback route
     */
    public function register_callback_route() {
        register_rest_route( 'lwg/v1', '/callback', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'handle_callback' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * Generate the Google OAuth URL
     */
    public function get_auth_url() {
        $options = get_option( 'lwg_settings', array() );

        if ( empty( $options['client_id'] ) ) {
            return '#';
        }

        // Generate a state token for CSRF protection
        $state = wp_create_nonce( 'lwg_google_auth' );

        // Store the redirect URL in a transient
        $redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : '';
        if ( empty( $redirect_to ) && ! empty( $options['redirect_after_login'] ) ) {
            $redirect_to = $options['redirect_after_login'];
        }
        if ( empty( $redirect_to ) ) {
            $redirect_to = home_url();
        }
        set_transient( 'lwg_redirect_' . $state, $redirect_to, 10 * MINUTE_IN_SECONDS );

        $params = array(
            'client_id'     => $options['client_id'],
            'redirect_uri'  => home_url( '/wp-json/lwg/v1/callback' ),
            'response_type' => 'code',
            'scope'         => 'email profile openid',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        );

        return self::GOOGLE_AUTH_URL . '?' . http_build_query( $params );
    }

    /**
     * Handle the auth redirect (initiate flow)
     */
    public function handle_auth_redirect() {
        if ( isset( $_GET['lwg_action'] ) && $_GET['lwg_action'] === 'login' ) {
            if ( is_user_logged_in() ) {
                wp_redirect( home_url() );
                exit;
            }
            wp_redirect( $this->get_auth_url() );
            exit;
        }
    }

    /**
     * Handle the OAuth callback from Google
     */
    public function handle_callback( $request ) {
        $code  = $request->get_param( 'code' );
        $state = $request->get_param( 'state' );
        $error = $request->get_param( 'error' );

        // Handle errors from Google
        if ( ! empty( $error ) ) {
            $this->redirect_with_error( __( 'Google authentication was cancelled or failed.', 'login-with-google' ) );
            return;
        }

        // Validate state (CSRF protection)
        if ( ! wp_verify_nonce( $state, 'lwg_google_auth' ) ) {
            $this->redirect_with_error( __( 'Security verification failed. Please try again.', 'login-with-google' ) );
            return;
        }

        // Validate code
        if ( empty( $code ) ) {
            $this->redirect_with_error( __( 'No authorization code received from Google.', 'login-with-google' ) );
            return;
        }

        // Exchange code for access token
        $token_data = $this->exchange_code_for_token( $code );
        if ( is_wp_error( $token_data ) ) {
            $this->redirect_with_error( $token_data->get_error_message() );
            return;
        }

        // Get user info from Google
        $google_user = $this->get_google_user( $token_data['access_token'] );
        if ( is_wp_error( $google_user ) ) {
            $this->redirect_with_error( $google_user->get_error_message() );
            return;
        }

        // Login or register the user
        $user = $this->login_or_register( $google_user );
        if ( is_wp_error( $user ) ) {
            $this->redirect_with_error( $user->get_error_message() );
            return;
        }

        // Log the user in
        wp_clear_auth_cookie();
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );

        // Update the last login meta
        update_user_meta( $user->ID, 'lwg_last_login', current_time( 'mysql' ) );
        update_user_meta( $user->ID, 'lwg_google_avatar', $google_user['picture'] ?? '' );

        // Get redirect URL from transient
        $redirect_to = get_transient( 'lwg_redirect_' . $state );
        delete_transient( 'lwg_redirect_' . $state );

        if ( empty( $redirect_to ) ) {
            $options = get_option( 'lwg_settings', array() );
            $redirect_to = ! empty( $options['redirect_after_login'] ) ? $options['redirect_after_login'] : home_url();
        }

        wp_redirect( $redirect_to );
        exit;
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchange_code_for_token( $code ) {
        $options = get_option( 'lwg_settings', array() );

        $response = wp_remote_post( self::GOOGLE_TOKEN_URL, array(
            'timeout' => 30,
            'body'    => array(
                'code'          => $code,
                'client_id'     => $options['client_id'],
                'client_secret' => $options['client_secret'],
                'redirect_uri'  => home_url( '/wp-json/lwg/v1/callback' ),
                'grant_type'    => 'authorization_code',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'token_error', __( 'Failed to communicate with Google. Please try again.', 'login-with-google' ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'token_error', sprintf( __( 'Google token error: %s', 'login-with-google' ), $body['error_description'] ?? $body['error'] ) );
        }

        if ( empty( $body['access_token'] ) ) {
            return new WP_Error( 'token_error', __( 'No access token received from Google.', 'login-with-google' ) );
        }

        return $body;
    }

    /**
     * Get user info from Google using access token
     */
    private function get_google_user( $access_token ) {
        $response = wp_remote_get( self::GOOGLE_USER_URL, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'user_error', __( 'Failed to get user info from Google.', 'login-with-google' ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['email'] ) ) {
            return new WP_Error( 'user_error', __( 'No email address received from Google.', 'login-with-google' ) );
        }

        return $body;
    }

    /**
     * Login existing user or register new user
     */
    private function login_or_register( $google_user ) {
        $email = sanitize_email( $google_user['email'] );

        // Check if user already exists by email
        $existing_user = get_user_by( 'email', $email );

        if ( $existing_user ) {
            // User exists — update Google meta and return
            update_user_meta( $existing_user->ID, 'lwg_google_id', $google_user['id'] );
            update_user_meta( $existing_user->ID, 'lwg_google_linked', 1 );

            /**
             * Action fired when an existing user logs in via Google.
             *
             * @param WP_User $existing_user The WordPress user object.
             * @param array   $google_user   The Google user profile data.
             */
            do_action( 'lwg_user_logged_in', $existing_user, $google_user );

            return $existing_user;
        }

        // Check if registration is allowed
        if ( ! get_option( 'users_can_register' ) ) {
            return new WP_Error( 'registration_disabled', __( 'User registration is currently disabled. Please contact the site administrator.', 'login-with-google' ) );
        }

        // Register new user
        $options = get_option( 'lwg_settings', array() );
        $default_role = ! empty( $options['default_role'] ) ? $options['default_role'] : get_option( 'default_role', 'subscriber' );

        // Generate a unique username from the email
        $username = sanitize_user( strstr( $email, '@', true ) );
        $original_username = $username;
        $counter = 1;
        while ( username_exists( $username ) ) {
            $username = $original_username . $counter;
            $counter++;
        }

        // Create the user
        $user_data = array(
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => wp_generate_password( 24, true, true ),
            'first_name'   => sanitize_text_field( $google_user['given_name'] ?? '' ),
            'last_name'    => sanitize_text_field( $google_user['family_name'] ?? '' ),
            'display_name' => sanitize_text_field( $google_user['name'] ?? $username ),
            'role'         => $default_role,
        );

        $user_id = wp_insert_user( $user_data );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        // Save Google meta data
        update_user_meta( $user_id, 'lwg_google_id', $google_user['id'] );
        update_user_meta( $user_id, 'lwg_google_linked', 1 );
        update_user_meta( $user_id, 'lwg_registered_via_google', 1 );
        update_user_meta( $user_id, 'lwg_google_avatar', $google_user['picture'] ?? '' );

        $new_user = get_user_by( 'ID', $user_id );

        /**
         * Action fired when a new user registers via Google.
         *
         * @param WP_User $new_user    The newly created WordPress user object.
         * @param array   $google_user The Google user profile data.
         */
        do_action( 'lwg_user_registered', $new_user, $google_user );

        return $new_user;
    }

    /**
     * Redirect to login page with error message
     */
    private function redirect_with_error( $message ) {
        $redirect_url = wp_login_url();
        $redirect_url = add_query_arg( 'lwg_error', urlencode( $message ), $redirect_url );
        wp_redirect( $redirect_url );
        exit;
    }
}
