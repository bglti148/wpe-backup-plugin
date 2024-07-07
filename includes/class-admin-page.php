<?php

class WP_Engine_Backup_Admin_Page {
    private $api_handler;

    /**
     * Constructor: Initialize the admin page
     *
     * @param WP_Engine_API_Handler $api_handler
     */
    public function __construct($api_handler) {
        $this->api_handler = $api_handler;
    }

    /**
     * Add the admin menu item
     */
    public function add_admin_menu() {
        add_menu_page(
            'WP Engine Backup Manager',
            'WPE Backups',
            'manage_options',
            'wp-engine-backup-manager',
            array($this, 'display_admin_page'),
            'dashicons-backup'
        );
    }

    /**
     * Register settings for the admin page
     */
    public function register_settings() {
        register_setting('wp_engine_backup_manager_settings', 'wpe_api_user_id');
        register_setting('wp_engine_backup_manager_settings', 'wpe_api_password');
    }

    /**
     * Display the admin page
     */
    public function display_admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Engine Backup Manager</h1>
            
            <!-- API Credentials Form -->
            <h2>API Credentials</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_engine_backup_manager_settings');
                do_settings_sections('wp_engine_backup_manager_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">WP Engine API User ID</th>
                        <td><input type="text" name="wpe_api_user_id" value="<?php echo esc_attr(get_option('wpe_api_user_id')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">WP Engine API Password</th>
                        <td><input type="password" name="wpe_api_password" value="<?php echo esc_attr(get_option('wpe_api_password')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Credentials'); ?>
            </form>

            <!-- Credential Validation Status -->
            <?php
            $user_id = get_option('wpe_api_user_id');
            $password = get_option('wpe_api_password');
            
            if (!empty($user_id) && !empty($password)) {
                $validation_result = $this->api_handler->validate_credentials();
                if ($validation_result['valid']) {
                    echo '<div class="notice notice-success"><p>' . esc_html($validation_result['message']) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html($validation_result['message']) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-warning"><p>Please enter your API credentials and save them.</p></div>';
            }
            ?>

            <!-- Backup Creation Form -->
            <h2>Create Backup</h2>
            <?php
            if (!empty($user_id) && !empty($password) && isset($validation_result['valid']) && $validation_result['valid']) {
                $install_id = $this->get_current_install_id();
                if ($install_id) {
                    ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="create_backup">
                        <?php wp_nonce_field('create_backup_nonce', 'backup_nonce'); ?>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">Backup Description</th>
                                <td><input type="text" name="backup_description" /></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">Notification Email</th>
                                <td><input type="email" name="notification_email" value="<?php echo esc_attr(get_option('admin_email')); ?>" required /></td>
                            </tr>
                        </table>
                        <?php submit_button('Create Backup'); ?>
                    </form>
                    <?php
                } else {
                    echo '<p>Unable to detect the current install ID. Please check your WP Engine configuration.</p>';
                }
            } else {
                echo '<p>Please enter valid API credentials above and save them before creating a backup.</p>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Display admin notices
     */
    public function display_admin_notices() {
        $messages = get_transient('wp_engine_backup_manager_messages');
        if ($messages) {
            foreach ($messages as $message) {
                echo '<div class="' . $message['type'] . '"><p>' . $message['message'] . '</p></div>';
            }
            delete_transient('wp_engine_backup_manager_messages');
        }
    }

    /**
     * Get the current install ID
     *
     * @return string|false
     */
    private function get_current_install_id() {
        $installs = $this->api_handler->get_installs();
        if (!$installs || !isset($installs['results'])) {
            return false;
        }

        $current_domain = parse_url(get_site_url(), PHP_URL_HOST);
        foreach ($installs['results'] as $install) {
            if ($install['cname'] === $current_domain || $install['primary_domain'] === $current_domain) {
                return $install['id'];
            }
        }

        return false;
    }
}