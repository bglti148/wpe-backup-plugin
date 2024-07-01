<?php
class WPEngine_Backup_Admin_Page {
    private $backup;

    public function __construct($backup) {
        $this->backup = $backup;
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Engine Backup',
            'WP Engine Backup',
            'manage_options',
            'wpengine-backup',
            array($this, 'display_admin_page')
        );
    }

    public function register_settings() {
        register_setting('wpengine_backup_options', 'wpengine_user_id');
        register_setting('wpengine_backup_options', 'wpengine_password');
    }

    public function enqueue_admin_scripts($hook) {
        if ('settings_page_wpengine-backup' !== $hook) {
            return;
        }
        wp_enqueue_style('wpengine-backup-admin-css', WPENGINE_BACKUP_PLUGIN_URL . 'assets/css/admin-style.css', array(), '1.0.0');
        wp_enqueue_script('wpengine-backup-admin-js', WPENGINE_BACKUP_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), '1.0.0', true);
    }

    public function display_admin_page() {
        include WPENGINE_BACKUP_PLUGIN_PATH . 'templates/admin-page.php';
    }

    public function process_form_submission() {
        if (isset($_POST['wpengine_install_id']) && check_admin_referer('wpengine_select_install', 'wpengine_select_install_nonce')) {
            update_option('wpengine_install_id', sanitize_text_field($_POST['wpengine_install_id']));
            add_settings_error('wpengine_messages', 'wpengine_message', __('Install selected successfully.', 'wpengine-backup-plugin'), 'updated');
        }

        if (isset($_POST['trigger_backup']) && check_admin_referer('wpengine_backup_trigger', 'wpengine_backup_nonce')) {
            $install_id = get_option('wpengine_install_id');
            $result = $this->backup->trigger_backup($install_id);
            if ($result && isset($result->id)) {
                add_settings_error('wpengine_messages', 'wpengine_message', __('Backup triggered successfully. Backup ID: ', 'wpengine-backup-plugin') . $result->id, 'updated');
            } else {
                add_settings_error('wpengine_messages', 'wpengine_message', __('Error triggering backup. Please check your credentials and try again.', 'wpengine-backup-plugin'), 'error');
            }
        }
    }
}