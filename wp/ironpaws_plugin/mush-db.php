<?php

const DEBUG=true;

class MushDB {
    //protected const SERVERNAME = "supermooseapps.com";
    protected const SERVERNAME = "localhost";
    protected const USERNAME = "bryany_mushuser";
    protected const PASSWORD = '9E{y)E;32.Qep7%m';

    static function connect() {
        try {
            $conn = new PDO("mysql:host=" . MushDB::SERVERNAME . ";dbname=bryany_mush", 
                MushDB::USERNAME, MushDB::PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
        catch(PDOException $e) {
            if (DEBUG) {
                die ("Got $e while connecting to mush.");
            } else {
                die ( "The database returned an error while connecting.");
            }
        }
        
        return $conn;
    }
}

// Returns: 0 if the number is invalid
function test_number($number) {
    if (is_numeric(test_input($number))) { 
      return $number; }
    else {
      $number = 0; }
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>