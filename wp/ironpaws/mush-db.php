<?php
    defined( 'ABSPATH' ) || exit;

    const DEBUG=true;

    class MySql {
        static public $reconnectErrors = [
            1317, // interrupted
            2002, // refused
            2006, // CR_SERVER_GONE_ERROR
            2013 // CR_SERVER_LOST
        ];
    }

    class MushDBException extends Exception {
        static public function throwErrorCoreException(string $errorCore, int $instance) {
            $this->message = "Creating the $errorCore was unsuccessful[{$instance}]";
            $this->code = 0;
            throw $this;
        }
    }

    class MushDB {
        //protected const SERVERNAME = "supermooseapps.com";
        protected const SERVERNAME = "localhost";
        protected const USERNAME = "bryan_mushuser";
        protected const PASSWORD = '9E{y)E;32.Qep7%m';
        protected const DBNAME = 'bryan_mush';
        
        protected $maxReconnectTries = 100;

        protected $reconnectTries = 0;
        protected $reconnectDelay = 400; // in ms
        protected $user;

        protected $conn;

        public function __construct() {
            $this->connect();
        }

        public function connect() {
            $this->conn = new PDO("mysql:host=" . MushDB::SERVERNAME . ";dbname=" . MushDB::DBNAME, 
                MushDB::USERNAME, MushDB::PASSWORD);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }

        // @param $errorCore The "core" of the error message to display.
        public function queryAndGetInsertId(string $statement, 
            array $params,
            string $errorCore) {

            $stmt = $this->execSql($statement, $params);

            if (is_null($stmt)) {
                MushDBException::throwErrorCoreException($errorCore, 0);
            }

            // PDO::lastInsertId() may have issues with stored procedures
            // https://stackoverflow.com/questions/15562478/php-mysql-pdo-lastinsertid-is-returning-0-when-using-a-procedure-to-insert-rows
            $rawId = $stmt->fetchAll(PDO::FETCH_NUM);
            $stmt->closeCursor();

            if (0 == $rawId) {
                MushDBException::throwErrorCoreException($errorCore, 1);
            }

            $id = $rawId[0][0];

            if (0 == $id) {
                MushDbException::throwErrorCoreException($errorCore, 2);
            }

            return $id;
        }
        
        // based off of:
        // https://www.tobymackenzie.com/blog/2020/08/18/automatic-reconnect-pdo-connection-time-out/
        public function execSql(string $statement, array $params) { // TODO: See if we really need an empty array.
            $this->reconnectTries = 0;

            while($this->reconnectTries < $this->maxReconnectTries) {
                if($params) {       
                    try{
                        // Turn off the display of errors so we don't see packets
                        // out of order. We allready deal with that.
                        statement_log(__FUNCTION__, __LINE__, "prepare:", $statement);
                        @$prepared = $this->conn->prepare($statement);
                        statement_log(__FUNCTION__, __LINE__, "execute:", $params);
                        if (@$prepared->execute($params)) {
                            return $prepared;
                        }
                        else {
                            return null;
                        }
                    }   // Retry case
                    catch(Exception $e) {
                        if (isset($e->errorInfo) && 
                            (in_array($e->errorInfo[1], MySql::$reconnectErrors))) {
                                write_log("...error is mysql");
                                $this->conn = null;
                                usleep($this->reconnectDelay * 1000);
                                ++$this->reconnectTries;
                                $this->connect();
                                continue; 
                        
                        } else {
                            statement_log(__FUNCTION__, __LINE__, "Caught exception", $e);

                            if (is_wp_debug()) {
                                var_dump($e);
                            }
                            throw new Exception("Encountered a network error that cannot be retried. Out of options."); 
                        }
                    }
                }     
                else {
                    throw new Exception(__FUNCTION__ . __LINE__ . ": Params is null");
                }
            }

            //Out of retries. Let the user know.
            throw new Exception("Retried $this->maxConnectRetries times. I couldn't make the network work.");        
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

    function makeSqlString(string $value) {
        return '\'' . $value . '\'';
    }
?>