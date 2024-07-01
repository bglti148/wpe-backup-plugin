<?php
class WPEngine_API {
    private $api_base_url = 'https://api.wpengineapi.com/v1';

    public function get_installs() {
        $user_id = get_option('wpengine_user_id');
        $password = get_option('wpengine_password');

        if (empty($user_id) || empty($password)) {
            return false;
        }

        $url = $this->api_base_url . "/installs";
        $args = $this->get_api_args('GET', $user_id, $password);

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

    public function validate_credentials() {
        $user_id = get_option('wpengine_user_id');
        $password = get_option('wpengine_password');

        if (empty($user_id) || empty($password)) {
            return false;
        }

        $url = $this->api_base_url . "/user";
        $args = $this->get_api_args('GET', $user_id, $password);

        $response = wp_remote_get($url, $args);

        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    public function trigger_backup($install_id) {
        $user_id = get_option('wpengine_user_id');
        $password = get_option('wpengine_password');

        if (empty($user_id) || empty($password) || empty($install_id)) {
            return false;
        }

        $url = $this->api_base_url . "/installs/{$install_id}/backups";
        $args = $this->get_api_args('POST', $user_id, $password);
        $args['body'] = json_encode(array(
            'description' => 'Backup triggered from WordPress plugin',
            'notification_emails' => array(get_option('admin_email'))
        ));

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body);
    }

    private function get_api_args($method, $user_id, $password) {
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($user_id . ':' . $password),
                'Content-Type' => 'application/json'
            )
        );
        return $args;
    }
}