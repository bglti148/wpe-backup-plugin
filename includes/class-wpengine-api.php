<?php
class WPEngine_API {
    private $api_base_url = 'https://api.wpengineapi.com/v1';

    
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
    
    // Method to get the current site's install ID.

    public function get_current_install_id() {
        $installs = $this->get_installs();
        if (!$installs) {
            return false;
        }
    
        $current_domain = parse_url(get_site_url(), PHP_URL_HOST);
        $best_match = null;
        $best_match_score = 0;
    
        foreach ($installs as $install) {
            // Check against cname
            $cname_score = $this->domain_similarity($current_domain, $install->cname);
            if ($cname_score > $best_match_score) {
                $best_match = $install;
                $best_match_score = $cname_score;
            }
            
            // Check against primary_domain
            $primary_domain_score = $this->domain_similarity($current_domain, $install->primary_domain);
            if ($primary_domain_score > $best_match_score) {
                $best_match = $install;
                $best_match_score = $primary_domain_score;
            }
        }
    
        return $best_match ? $best_match->id : false;
    }
    
    private function domain_matches($domain1, $domain2) {
        // Remove 'www.' if present
        $domain1 = preg_replace('/^www\./', '', $domain1);
        $domain2 = preg_replace('/^www\./', '', $domain2);
    
        // Check for exact match
        if ($domain1 === $domain2) {
            return true;
        }
    
        // Check if one domain is a subdomain of the other
        if (strpos($domain1, $domain2) !== false || strpos($domain2, $domain1) !== false) {
            return true;
        }
    
        return false;
    }
    
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

    private function domain_similarity($domain1, $domain2) {
        $domain1 = strtolower(preg_replace('/^www\./', '', $domain1));
        $domain2 = strtolower(preg_replace('/^www\./', '', $domain2));
    
        if ($domain1 === $domain2) {
            return 100;
        }
    
        $parts1 = explode('.', $domain1);
        $parts2 = explode('.', $domain2);
    
        $score = 0;
        $max_parts = max(count($parts1), count($parts2));
    
        for ($i = 1; $i <= $max_parts; $i++) {
            $part1 = isset($parts1[count($parts1) - $i]) ? $parts1[count($parts1) - $i] : '';
            $part2 = isset($parts2[count($parts2) - $i]) ? $parts2[count($parts2) - $i] : '';
    
            if ($part1 === $part2) {
                $score += (1 / $max_parts) * 100;
            } else {
                break;
            }
        }
    
        return $score;
    }

    

    public function trigger_backup($install_id, $description = '') {
        $user_id = get_option('wpengine_user_id');
        $password = get_option('wpengine_password');
    
        if (empty($user_id) || empty($password) || empty($install_id)) {
            return false;
        }
    
        $url = $this->api_base_url . "/installs/{$install_id}/backups";
        $args = $this->get_api_args('POST', $user_id, $password);
        
        $backup_description = !empty($description) ? $description : 'Backup triggered from WordPress plugin';
        
        $args['body'] = json_encode(array(
            'description' => $backup_description,
            'notification_emails' => array(get_option('admin_email'))
        ));
    
        // error_log('API Request: ' . print_r($args, true));  // Debug line
    
        $response = wp_remote_post($url, $args);
    
        if (is_wp_error($response)) {
            // error_log('API Error: ' . $response->get_error_message());  // Debug line
            return $response;
        }
    
        $body = wp_remote_retrieve_body($response);
        // error_log('API Response Body: ' . $body);  // Debug line
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

    public function validate_credentials_with_values($user_id, $password) {
        if (empty($user_id) || empty($password)) {
            return false;
        }
    
        $url = $this->api_base_url . "/user";
        $args = $this->get_api_args('GET', $user_id, $password);
    
        $response = wp_remote_get($url, $args);
    
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
}