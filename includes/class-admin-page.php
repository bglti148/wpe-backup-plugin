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
        $this->process_form_submission();
        $install_id = $this->backup->get_current_install_id();
        $site_url = get_site_url();
        
        include WPENGINE_BACKUP_PLUGIN_PATH . 'templates/admin-page.php';
    }

    public function process_form_submission() {
        if (isset($_POST['trigger_backup']) && check_admin_referer('wpengine_backup_trigger', 'wpengine_backup_nonce')) {
            $install_id = $this->backup->get_current_install_id();
            if (!$install_id) {
                add_settings_error('wpengine_messages', 'wpengine_message', __('Unable to determine the current install ID. Please check your WP Engine API credentials.', 'wpengine-backup-plugin'), 'error');
                return;
            }
    
            $description = isset($_POST['backup_description']) ? sanitize_text_field($_POST['backup_description']) : '';
            // error_log('Description from form: ' . $description);  // Debug line
            
            $result = $this->backup->trigger_backup($install_id, $description);
            if ($result && isset($result->id)) {
                add_settings_error('wpengine_messages', 'wpengine_message', __('Backup triggered successfully. Backup ID: ', 'wpengine-backup-plugin') . $result->id, 'updated');
            } else {
                add_settings_error('wpengine_messages', 'wpengine_message', __('Error triggering backup. Please check your credentials and try again.', 'wpengine-backup-plugin'), 'error');
            }
            // error_log('API Response: ' . print_r($result, true));  // Debug line
        }
    }
}