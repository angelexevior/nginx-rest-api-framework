<?php

class beepconfig {

    //Database configuration
    public $db_hostname = '';
    public $db_username = '';
    public $db_password = '';
    public $beep_database = '';
   
    //The time a session should be left alive (In seconds)
    //This is for security reasons. Users will be automatically logged out after the specified seconds of inactivity
    public $session_timeout = '10800';
    
    //Email settings
    public $smtphost = "";
    public $smtpusername = "donotreply@email.com";
    public $smtppassword = "";
    public $smtpmode = "";
    public $smtpport = "";
    //Time settings
    public $timezone = 'UTC';
    public $debug_queries = null;

    function __construct() {
        
        $servername = explode('.', $_SERVER['HTTP_HOST']);
        $debug_queries = 0;
        //    $debug_queries = 1;
    }
}
?>
