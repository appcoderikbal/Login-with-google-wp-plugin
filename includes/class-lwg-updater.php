<?php
/**
 * GitHub Auto-Updater
 *
 * Checks GitHub releases/tags for plugin updates and enables
 * updating through the WordPress dashboard.
 *
 * @package Login_With_Google
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LWG_Updater {

    /**
     * GitHub repository details
     */
    private $username = 'appcoderikbal';
    private $repo     = 'Login-with-google-wp-plugin';
    private $basename;
    private $plugin_file;
    private $cache_key = 'lwg_update_check';

    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin_file = LWG_PLUGIN_FILE;
        $this->basename    = LWG_PLUGIN_BASENAME;

        // Check for updates
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );

        // Plugin information popup
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );

        // After update, rename folder to match expected structure
        add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );

        // Add "Check for Update" action link
        add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'add_update_link' ) );

        // Handle forced update check
        add_action( 'admin_init', array( $this, 'handle_force_check' ) );
    }

    /**
     * Add "Check for Update" link to plugin actions
     */
    public function add_update_link( $links ) {
        $update_url = add_query_arg( array( 'lwg_check_update' => 1 ), admin_url( 'plugins.php' ) );
        $links[] = '<a href="' . esc_url( $update_url ) . '" style="color: #4285F4; font-weight: bold;">' . __( 'Check for Update', 'login-with-google' ) . '</a>';
        return $links;
    }

    /**
     * Handle forced update check
     */
    public function handle_force_check() {
        if ( isset( $_GET['lwg_check_update'] ) ) {
            delete_transient( $this->cache_key );
            delete_site_transient( 'update_plugins' );
            wp_redirect( remove_query_arg( 'lwg_check_update' ) );
            exit;
        }
    }

    /**
     * Get remote release data from GitHub
     */
    private function get_remote_data() {
        $remote_data = get_transient( $this->cache_key );

        if ( false !== $remote_data ) {
            return $remote_data;
        }

        // Try Releases API first (preferred)
        $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";
        $response = wp_remote_get( $url, array(
            'timeout'    => 15,
            'user-agent' => 'Login-With-Google-Updater',
            'headers'    => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        ) );

        if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
            $release = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $release['tag_name'] ) ) {
                $remote_data = array(
                    'version'     => str_replace( 'v', '', $release['tag_name'] ),
                    'download_url'=> $release['zipball_url'],
                    'changelog'   => ! empty( $release['body'] ) ? $release['body'] : 'See GitHub for changelog.',
                    'published'   => $release['published_at'] ?? '',
                );
                set_transient( $this->cache_key, $remote_data, 12 * HOUR_IN_SECONDS );
                return $remote_data;
            }
        }

        // Fallback to Tags API
        $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/tags";
        $response = wp_remote_get( $url, array(
            'timeout'    => 15,
            'user-agent' => 'Login-With-Google-Updater',
        ) );

        if ( ! is_wp_error( $response ) ) {
            $tags = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( is_array( $tags ) && ! empty( $tags ) ) {
                $remote_data = array(
                    'version'      => str_replace( 'v', '', $tags[0]['name'] ),
                    'download_url' => $tags[0]['zipball_url'],
                    'changelog'    => 'Check GitHub for full changelog.',
                    'published'    => '',
                );
                set_transient( $this->cache_key, $remote_data, 12 * HOUR_IN_SECONDS );
                return $remote_data;
            }
        }

        return false;
    }

    /**
     * Check for plugin updates
     */
    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote_data = $this->get_remote_data();

        if ( ! $remote_data || empty( $remote_data['version'] ) ) {
            return $transient;
        }

        $current_version = LWG_VERSION;
        $remote_version  = $remote_data['version'];

        if ( version_compare( $remote_version, $current_version, '>' ) ) {
            $res              = new stdClass();
            $res->slug        = $this->basename;
            $res->plugin      = $this->basename;
            $res->new_version = $remote_version;
            $res->url         = "https://github.com/{$this->username}/{$this->repo}";
            $res->package     = $remote_data['download_url'];
            $res->tested      = get_bloginfo( 'version' );
            $res->icons       = array(
                'default' => LWG_PLUGIN_URL . 'assets/img/icon-128.png',
            );

            $transient->response[ $this->basename ] = $res;
        }

        return $transient;
    }

    /**
     * Plugin information for the popup
     */
    public function plugin_info( $res, $action, $args ) {
        if ( 'plugin_information' !== $action || ! isset( $args->slug ) || $args->slug !== $this->basename ) {
            return $res;
        }

        $remote_data = $this->get_remote_data();

        if ( ! $remote_data ) {
            return $res;
        }

        $res                = new stdClass();
        $res->name          = 'Login with Google';
        $res->slug          = $this->basename;
        $res->version       = $remote_data['version'];
        $res->tested        = get_bloginfo( 'version' );
        $res->requires      = '5.0';
        $res->requires_php  = '7.4';
        $res->author        = '<a href="https://github.com/appcoderikbal">appcoderikbal</a>';
        $res->author_profile= 'https://github.com/appcoderikbal';
        $res->homepage      = "https://github.com/{$this->username}/{$this->repo}";
        $res->download_link = $remote_data['download_url'];
        $res->trunk         = $remote_data['download_url'];
        $res->last_updated  = $remote_data['published'];
        $res->sections      = array(
            'description' => 'Allow users to login or register on your WordPress site using their Google account. Simple, secure, and easy to configure.',
            'changelog'   => nl2br( esc_html( $remote_data['changelog'] ) ),
            'installation'=> '<ol><li>Upload the plugin to your WordPress site</li><li>Activate it</li><li>Go to Settings → Login with Google</li><li>Enter your Google OAuth credentials</li><li>Use the shortcode [login_with_google] anywhere</li></ol>',
        );

        return $res;
    }

    /**
     * After update, rename the extracted folder to match our plugin slug
     */
    public function post_install( $response, $hook_extra, $result ) {
        // Only process our own plugin
        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
            return $result;
        }

        global $wp_filesystem;

        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname( $this->basename );
        $wp_filesystem->move( $result['destination'], $plugin_dir );
        $result['destination'] = $plugin_dir;

        // Re-activate the plugin
        activate_plugin( $this->basename );

        return $result;
    }
}
