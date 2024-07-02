<?php
/**
 * Plugin Name: WP Engine Backup Plugin
 * Description: Allows admin users to trigger a backup of their WP Engine-hosted website.
 * Version: 1.0
 * Author: Ellis LaMay
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPENGINE_BACKUP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPENGINE_BACKUP_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WPENGINE_BACKUP_PLUGIN_PATH . 'includes/class-wpengine-api.php';
require_once WPENGINE_BACKUP_PLUGIN_PATH . 'includes/class-wpengine-backup.php';
require_once WPENGINE_BACKUP_PLUGIN_PATH . 'includes/class-admin-page.php';

function wpengine_backup_plugin_init() {
    $api = new WPEngine_API();
    $backup = new WPEngine_Backup($api);
    $admin_page = new WPEngine_Backup_Admin_Page($backup);
}

add_action('plugins_loaded', 'wpengine_backup_plugin_init');


// AJAX action to validate the credentials

add_action('wp_ajax_validate_wpengine_credentials', 'validate_wpengine_credentials_callback');

function validate_wpengine_credentials_callback() {
    $user_id = $_POST['wpengine_user_id'];
    $password = $_POST['wpengine_password'];
    
    $api = new WPEngine_API();
    $valid = $api->validate_credentials_with_values($user_id, $password);
    
    wp_send_json_success(array('valid' => $valid));
}