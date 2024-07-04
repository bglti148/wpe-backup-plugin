<?php
/*
Plugin Name: WP Engine Backup Manager
Description: Manage WP Engine backups directly from your WordPress dashboard.
Version: 1.0
Author: Ellis LaMay
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'class-api-handler.php';

class WP_Engine_Backup_Manager {
    private static $instance = null;
    private $api_handler;

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_create_backup', array($this, 'handle_backup_creation'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        $this->api_handler = new WP_Engine_API_Handler();
    }

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

    public function register_settings() {
        register_setting('wp_engine_backup_manager_settings', 'wpe_api_user_id');
        register_setting('wp_engine_backup_manager_settings', 'wpe_api_password');
    }

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
                                <td><input type="text" name="backup_description" required /></td>
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

    public function handle_backup_creation() {
        if (!isset($_POST['backup_nonce']) || !wp_verify_nonce($_POST['backup_nonce'], 'create_backup_nonce')) {
            wp_die('Security check failed');
        }

        $install_id = $this->get_current_install_id();
        $description = sanitize_text_field($_POST['backup_description']);
        $email = sanitize_email($_POST['notification_email']);

        $result = $this->api_handler->create_backup($install_id, $description, $email);

        if ($result['success']) {
            add_settings_error('wp_engine_backup_manager', 'backup_created', 'Backup created successfully!', 'updated');
        } else {
            add_settings_error('wp_engine_backup_manager', 'backup_failed', 'Failed to create backup: ' . $result['error'], 'error');
        }

        set_transient('wp_engine_backup_manager_messages', get_settings_errors(), 30);

        wp_redirect(admin_url('admin.php?page=wp-engine-backup-manager'));
        exit;
    }

    public function display_admin_notices() {
        $messages = get_transient('wp_engine_backup_manager_messages');
        if ($messages) {
            foreach ($messages as $message) {
                echo '<div class="' . $message['type'] . '"><p>' . $message['message'] . '</p></div>';
            }
            delete_transient('wp_engine_backup_manager_messages');
        }
    }

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

    public static function deactivate() {
        delete_option('wpe_api_user_id');
        delete_option('wpe_api_password');
    }
}

// Initialize the plugin
function wp_engine_backup_manager_init() {
    WP_Engine_Backup_Manager::get_instance();
}
add_action('plugins_loaded', 'wp_engine_backup_manager_init');

// Register deactivation hook
register_deactivation_hook(__FILE__, array('WP_Engine_Backup_Manager', 'deactivate'));