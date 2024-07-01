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