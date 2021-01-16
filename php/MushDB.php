<?php

class MushDB {

    //protected const SERVERNAME = "supermooseapps.com";
    protected const SERVERNAME = "localhost";
    protected const USERNAME = "bryany_mushuser";
    protected const PASSWORD = '9E{y)E;32.Qep7%m';

    protected static $conn = null;

    static function connect() {
        if (null == MushDB::$conn) {
            try {
                MushDB::$conn = new PDO("mysql:host=" . MushDB::SERVERNAME . ";dbname=bryany_mush", 
                    MushDB::USERNAME, MushDB::PASSWORD);
                MushDB::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            catch(PDOException $e) {
                die ( "The database returned an error while connecting. The details are: " . $e->getMessage());
            }
        } 
        
        return MushDB::$conn;
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