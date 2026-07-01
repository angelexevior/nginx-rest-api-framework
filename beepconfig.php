<?php

class beepconfig {

    //Database configuration
    public $db_hostname = '';
    public $db_port = '3306';
    public $db_username = '';
    public $db_password = '';
    public $beep_database = '';
   
    //The time a session should be left alive (In seconds)
    //This is for security reasons. Users will be automatically logged out after the specified seconds of inactivity
    public $session_timeout = '10800';
    
    //Time settings
    public $timezone = 'UTC';
}

