<?php
/*
Plugin Name: WP Engine Backup Manager
Description: Manage WP Engine backups directly from your WordPress dashboard.
Version: 1.0
Author: Your Name
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/class-api-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-backup-manager.php';

class WP_Engine_Backup_Manager_Plugin {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $api_handler = new WP_Engine_API_Handler();
        $admin_page = new WP_Engine_Backup_Admin_Page($api_handler);
        $backup_manager = new WP_Engine_Backup_Manager($api_handler);

        add_action('admin_menu', array($admin_page, 'add_admin_menu'));
        add_action('admin_init', array($admin_page, 'register_settings'));
        add_action('admin_post_create_backup', array($backup_manager, 'handle_backup_creation'));
        add_action('admin_notices', array($admin_page, 'display_admin_notices'));
    }

    public static function deactivate() {
        delete_option('wpe_api_user_id');
        delete_option('wpe_api_password');
    }
}

// Initialize the plugin
function wp_engine_backup_manager_init() {
    WP_Engine_Backup_Manager_Plugin::get_instance();
}
add_action('plugins_loaded', 'wp_engine_backup_manager_init');

// Register deactivation hook
register_deactivation_hook(__FILE__, array('WP_Engine_Backup_Manager_Plugin', 'deactivate'));