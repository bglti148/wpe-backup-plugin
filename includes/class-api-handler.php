<?php

class WP_Engine_API_Handler {
    private $api_base_url = 'https://api.wpengineapi.com/v1';
    private $user_id;
    private $password;

    /**
     * Constructor: Initialize the API handler
     */
    public function __construct() {
        $this->user_id = get_option('wpe_api_user_id');
        $this->password = get_option('wpe_api_password');
    }

    /**
     * Get the authorization header for API requests
     *
     * @return string
     */
    private function get_auth_header() {
        return 'Basic ' . base64_encode($this->user_id . ':' . $this->password);
    }

    /**
     * Validate the API credentials
     *
     * @return array
     */
    public function validate_credentials() {
        // Check if credentials are set
        if (empty($this->user_id) || empty($this->password)) {
            return array('valid' => false, 'message' => 'API credentials are not set.');
        }

        // Make a request to the API to validate credentials
        $response = wp_remote_get($this->api_base_url . '/user', array(
            'headers' => array(
                'Authorization' => $this->get_auth_header(),
            ),
        ));

        // Check for WordPress errors
        if (is_wp_error($response)) {
            return array('valid' => false, 'message' => 'Failed to connect to WP Engine API: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        
        // Interpret the response
        if ($status_code === 200) {
            return array('valid' => true, 'message' => 'API credentials are valid.');
        } elseif ($status_code === 401) {
            return array('valid' => false, 'message' => 'Invalid API credentials. Please check your User ID and Password.');
        } else {
            return array('valid' => false, 'message' => 'Unexpected response from WP Engine API. Status code: ' . $status_code);
        }
    }

    /**
     * Get the list of installs from the API
     *
     * @return array|false
     */
    public function get_installs() {
        $response = wp_remote_get($this->api_base_url . '/installs', array(
            'headers' => array(
                'Authorization' => $this->get_auth_header(),
            ),
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Create a backup via the API
     *
     * @param string $install_id
     * @param string $description
     * @param string $email
     * @return array
     */
    public function create_backup($install_id, $description, $email) {
        $response = wp_remote_post($this->api_base_url . "/installs/{$install_id}/backups", array(
            'headers' => array(
                'Authorization' => $this->get_auth_header(),
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'description' => $description,
                'notification_emails' => array($email),
            )),
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        return array(
            'success' => $status_code === 202,
            'data' => json_decode($body, true),
        );
    }
}