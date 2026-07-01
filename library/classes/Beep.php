<?php

defined('_SECURED') or die('Restricted access');

class Beep {

    public $db;
    public $config;

    function __construct() {
        if (file_exists('beepconfig.php')) {
            include_once('beepconfig.php');
        } else {
            include_once('../beepconfig.php');
        }
        $this->config = new beepconfig();
    }

    public function connect_to_database() {
        if (file_exists('library/databasegeneral.php')) {
            include_once('library/databasegeneral.php');
        } else {
            include_once('../library/databasegeneral.php');
        }
        $this->db = new mysqlDatabase();
        $this->db->open_connection();
        return $this->db;
    }

    public function database_connected() {
        return isset($this->db);
    }

    public function database_close() {
        $this->db->close_connection();
    }

    public function getdata($query) {
        $result = $this->db->query($query);
        return $this->db->fetch_assoc($result);
    }
}
