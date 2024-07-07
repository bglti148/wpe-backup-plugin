<?php

class WP_Engine_Backup_Manager {
    private $api_handler;

    /**
     * Constructor: Initialize the backup manager
     *
     * @param WP_Engine_API_Handler $api_handler
     */
    public function __construct($api_handler) {
        $this->api_handler = $api_handler;
    }

    /**
     * Handle the backup creation process
     */
    public function handle_backup_creation() {
        // Verify nonce for security
        if (!isset($_POST['backup_nonce']) || !wp_verify_nonce($_POST['backup_nonce'], 'create_backup_nonce')) {
            wp_die('Security check failed');
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
        } else {
            add_settings_error('wp_engine_backup_manager', 'backup_failed', 'Failed to create backup: ' . $result['error'], 'error');
        }

        // Store messages for display
        set_transient('wp_engine_backup_manager_messages', get_settings_errors(), 30);

        // Redirect back to the admin page
        wp_redirect(admin_url('admin.php?page=wp-engine-backup-manager'));
        exit;
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