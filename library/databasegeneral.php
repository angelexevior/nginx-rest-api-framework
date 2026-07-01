<?php
defined('_SECURED') or die('Restricted access');

class mysqlDatabase {

    public $connection;
    public $last_query;

    public function open_connection() {
        $config = new beepconfig();
        $port = !empty($config->db_port) ? (int) $config->db_port : 3306;
        $this->connection = mysqli_connect($config->db_hostname, $config->db_username, $config->db_password, $config->beep_database, $port);
        if (!$this->connection) {
            die('Database connection failed: ' . mysqli_connect_error());
        }
        mysqli_set_charset($this->connection, 'utf8mb4');
    }

    public function close_connection() {
        if (isset($this->connection)) {
            mysqli_close($this->connection);
            unset($this->connection);
        }
    }

    public function query($sql) {
        $this->last_query = $sql;
        $result = mysqli_query($this->connection, $sql);
        $this->confirm_query($result);
        return $result;
    }

    public function escape_value($value) {
        return mysqli_real_escape_string($this->connection, $value);
    }

    public function fetch_assoc($result_set) {
        $assoc_arr = array();
        while ($row = mysqli_fetch_assoc($result_set)) {
            $assoc_arr[] = $row;
        }
        return $assoc_arr;
    }

    public function num_rows($result_set) {
        return mysqli_num_rows($result_set);
    }

    public function insert_id() {
        return mysqli_insert_id($this->connection);
    }

    public function affected_rows() {
        return mysqli_affected_rows($this->connection);
    }

    private function confirm_query($result) {
        if (!$result) {
            error_log('Database query failed: ' . mysqli_error($this->connection) . ' | Query: ' . $this->last_query);
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(array('error' => 'Internal server error'));
            die();
        }
    }

    public function free_result($result) {
        mysqli_free_result($result);
    }
}
