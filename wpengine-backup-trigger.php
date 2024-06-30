<?php
/**
 * Plugin Name: WP Engine Backup Trigger
 * Description: Allows admin users to trigger a backup of their WP Engine-hosted website.
 * Version: 1.0
 * Author: Your Name
 */

// Ensure this file is being run within the WordPress context
if (!defined('ABSPATH')) {
    exit;
}

class WPEngine_Backup_Trigger {
    private $api_base_url = 'https://api.wpengineapi.com/v1';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
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

    public function display_admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Engine Backup Trigger</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('wpengine_backup_options'); ?>
                <?php do_settings_sections('wpengine_backup_options'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">WP Engine User ID</th>
                        <td><input type="text" name="wpengine_user_id" value="<?php echo esc_attr(get_option('wpengine_user_id')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">WP Engine Password</th>
                        <td><input type="password" name="wpengine_password" value="<?php echo esc_attr(get_option('wpengine_password')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Credentials'); ?>
            </form>
    
            <?php
            $installs = $this->get_installs();
            if ($installs) {
                ?>
                <h2>Select Install</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('wpengine_select_install', 'wpengine_select_install_nonce'); ?>
                    <select name="wpengine_install_id">
                        <?php
                        foreach ($installs as $install) {
                            echo '<option value="' . esc_attr($install->id) . '"' . selected(get_option('wpengine_install_id'), $install->id, false) . '>' . esc_html($install->name) . ' (' . esc_html($install->id) . ')</option>';
                        }
                        ?>
                    </select>
                    <?php submit_button('Select Install'); ?>
                </form>
    
                <h2>Trigger Backup</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('wpengine_backup_trigger', 'wpengine_backup_nonce'); ?>
                    <p><input type="submit" name="trigger_backup" class="button button-primary" value="Trigger Backup" /></p>
                </form>
                <?php
            } else {
                echo '<div class="error"><p>Unable to fetch installs. Please check your credentials and try again.</p></div>';
            }
            ?>
        </div>
        <?php
    
        if (isset($_POST['wpengine_install_id']) && check_admin_referer('wpengine_select_install', 'wpengine_select_install_nonce')) {
            update_option('wpengine_install_id', sanitize_text_field($_POST['wpengine_install_id']));
            echo '<div class="updated"><p>Install selected successfully.</p></div>';
        }
    
        if (isset($_POST['trigger_backup']) && check_admin_referer('wpengine_backup_trigger', 'wpengine_backup_nonce')) {
            $this->trigger_backup();
        }
    }

    private function get_installs() {
        $user_id = get_option('wpengine_user_id');
        $password = get_option('wpengine_password');
    
        if (empty($user_id) || empty($password)) {
            return false;
        }
    
        $url = $this->api_base_url . "/installs";
        $args = array(
            'method' => 'GET',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($user_id . ':' . $password),
            ),
        );
    
        $response = wp_remote_get($url, $args);
    
        if (is_wp_error($response)) {
            return false;
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
    
        if ($data && isset($data->results)) {
            return $data->results;
        }
    
        return false;
    }

    private function validate_credentials() {
        $user_id = get_option('wpengine_user_id');
        $password = get_option('wpengine_password');
    
        if (empty($user_id) || empty($password)) {
            return false;
        }
    
        $url = $this->api_base_url . "/user";
        $args = array(
            'method' => 'GET',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($user_id . ':' . $password),
            ),
        );
    
        $response = wp_remote_get($url, $args);
    
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
    
        return true;
    }

    private function trigger_backup() {
        $user_id = get_option('wpengine_user_id');
        $password = get_option('wpengine_password');
        $install_id = get_option('wpengine_install_id');

        if (empty($user_id) || empty($password) || empty($install_id)) {
            echo '<div class="error"><p>Please fill in all WP Engine credentials.</p></div>';
            return;
        }

        $url = $this->api_base_url . "/installs/{$install_id}/backups";
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($user_id . ':' . $password),
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'description' => 'Backup triggered from WordPress plugin',
                'notification_emails' => array(get_option('admin_email'))
            ))
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            echo '<div class="error"><p>Error: ' . $response->get_error_message() . '</p></div>';
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            if ($data && isset($data->id)) {
                echo '<div class="updated"><p>Backup triggered successfully. Backup ID: ' . $data->id . '</p></div>';
            } else {
                echo '<div class="error"><p>Error triggering backup. Please check your credentials and try again.</p></div>';
            }
        }
    }
}

new WPEngine_Backup_Trigger();