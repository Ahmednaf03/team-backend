<?php

require_once 'Patient.php';
require_once 'User.php';
require_once 'Tenant.php';
require_once 'Appointment.php';

class ModelFactory {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function patient() {
        return new Patient($this->db);
    }

    public function user() {
        return new User($this->db);
    }

    public function tenant() {
        return new Tenant($this->db);
    }

    public function appointment() {
        return new Appointment($this->db);
    }
}
