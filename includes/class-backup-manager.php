<?php

class WP_Engine_Backup_Manager {
    private $api_handler;
    private $rate_limit_option = 'wpe_backup_last_request';
    private $rate_limit_interval = 1800; // 30 minutes in seconds

    public function __construct($api_handler) {
        $this->api_handler = $api_handler;
    }

    public function handle_backup_creation() {
        // Verify nonce for security
        if (!isset($_POST['backup_nonce']) || !wp_verify_nonce($_POST['backup_nonce'], 'create_backup_nonce')) {
            wp_die('Security check failed');
        }

        // Check rate limit
        if (!$this->check_rate_limit()) {
            add_settings_error('wp_engine_backup_manager', 'rate_limit', 'You can only create a backup once every 30 minutes. Please try again later.', 'error');
            set_transient('wp_engine_backup_manager_messages', get_settings_errors(), 30);
            wp_redirect(admin_url('admin.php?page=wp-engine-backup-manager'));
            exit;
        }

        $install_id = $this->get_current_install_id();
        $description = sanitize_text_field($_POST['backup_description']);
        $email = sanitize_email($_POST['notification_email']);

        // Append user's email to the description
        $description .= " (Triggered by $email)";

        // Create the backup
        $result = $this->api_handler->create_backup($install_id, $description, $email);

        // Handle the result
        if ($result['success']) {
            add_settings_error('wp_engine_backup_manager', 'backup_created', 'Backup created successfully!', 'updated');
            $this->update_last_request_time();
        } else {
            add_settings_error('wp_engine_backup_manager', 'backup_failed', 'Failed to create backup: ' . $result['error'], 'error');
        }

        // Store messages for display
        set_transient('wp_engine_backup_manager_messages', get_settings_errors(), 30);

        // Redirect back to the admin page
        wp_redirect(admin_url('admin.php?page=wp-engine-backup-manager'));
        exit;
    }

    private function check_rate_limit() {
        $last_request = get_option($this->rate_limit_option, 0);
        $current_time = time();
        return ($current_time - $last_request) >= $this->rate_limit_interval;
    }

    private function update_last_request_time() {
        update_option($this->rate_limit_option, time());
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