<?php
class WPEngine_Backup {
    private $api;

    public function __construct($api) {
        $this->api = $api;
    }

    public function get_current_install_id() {
        return $this->api->get_current_install_id();
    }

    public function validate_credentials() {
        return $this->api->validate_credentials();
    }

    public function trigger_backup($install_id, $description = '') {
        return $this->api->trigger_backup($install_id, $description);
    }

    // Add this method back
    public function get_installs() {
        return $this->api->get_installs();
    }

}