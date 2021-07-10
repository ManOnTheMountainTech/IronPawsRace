<?php

    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'includes/mysql.php';
    require_once plugin_dir_path(__FILE__) . 'includes/mush-db-exception.php';

    const DEBUG=true;

    class Mush_DB {
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
            $this->conn = new \PDO("mysql:host=" . Mush_DB::SERVERNAME . ";dbname=" . Mush_DB::DBNAME, 
                Mush_DB::USERNAME, Mush_DB::PASSWORD);
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        }

        // @param $errorCore The "core" of the error message to display.
        // @returns -> the column from the database
        public function execAndReturnColumn(string $statement, 
            array $params = [],
            string $errorCore) {

            $stmt = $this->execSql($statement, $params);

            if (is_null($stmt)) {
                Mush_DB_Exception::throwErrorCoreException($errorCore, 0);
            }

            $column = $stmt->fetchAll(\PDO::FETCH_NUM);
            $stmt->closeCursor();

            if (is_null($column)) {
                Mush_Db_Exception::throwErrorCoreException($errorCore, 2);
            }

            return $column;
        }

        // @param $errorCore The "core" of the error message to display.
        // @returns -> the returned id
        public function execAndReturnInt(string $statement, 
            array $params = [],
            string $errorCore) {

            $stmt = $this->execSql($statement, $params);

            if (is_null($stmt)) {
                Mush_DB_Exception::throwErrorCoreException($errorCore, 0);
            }

            // PDO::lastInsertId() may have issues with stored procedures
            // https://stackoverflow.com/questions/15562478/php-mysql-pdo-lastinsertid-is-returning-0-when-using-a-procedure-to-insert-rows
            $rawId = $stmt->fetchAll(\PDO::FETCH_NUM);
            $stmt->closeCursor();

            if (0 == $rawId) {
                Mush_DB_Exception::throwErrorCoreException($errorCore, 1);
            }

            // Result always comes back in the array of the array for single object returns.
            $id = $rawId[0][0];

            if (0 == $id) {
                Mush_Db_Exception::throwErrorCoreException($errorCore, 2);
            }

            return $id;
        }

        public function preparedExec(string $statement, array $params = []) {
            // Turn off the display of errors so we don't see packets
            // out of order. We allready deal with that.
            @$prepared = $this->conn->prepare($statement);
            if (@$prepared->execute($params)) {
                return $prepared;
            }
            else {
                return null;
            }
        }

        public function query(string $statement) {
            return $this->conn->query($statement);
        }
        
        // based off of:
        // https://www.tobymackenzie.com/blog/2020/08/18/automatic-reconnect-pdo-connection-time-out/
        public function execSql(string $statement, array $params = []) { // TODO: See if we really need an empty array.
            $this->reconnectTries = 0;

            while($this->reconnectTries < $this->maxReconnectTries) {
                if($params) {       
                    try{
                        return (empty($params)) ? $this->query($statement) : $this->preparedExec($statement, $params);
                    }   // Retry case
                    // higher-level exception handlers will catch more specific exceptions.
                    // So catch here so that the retry case works.
                    catch(\PDOException | \Exception $e) {
                        if (isset($e->errorInfo) && 
                            (in_array($e->errorInfo[1], MySql::$reconnectErrors))) {
                                $this->conn = null;
                                usleep($this->reconnectDelay * 1000);
                                ++$this->reconnectTries;
                                $this->connect();
                                continue; 
                        
                        } else {
                            statement_log(__FUNCTION__, __LINE__, "Caught exception", $e);
                            statement_log(__FUNCTION__, __LINE__, "prepare:", $statement);
                            statement_log(__FUNCTION__, __LINE__, "execute:", $params);

                            if (is_wp_debug()) {
                                var_dump($e);
                                    return "Unhandled MySQL exception";
                            }

                            // TODO
                            throw new Mush_DB_Exception("Encountered a network error that cannot be retried. Out of options."); 
                        }
                    }
                }     
                else {
                    throw new Mush_DB_Exception(__FUNCTION__ . __LINE__ . ": Params is null");
                }
            }

            //Out of retries. Let the user know.
            throw new Mush_DB_Exception("Retried $this->maxConnectRetries times. I couldn't make the network work.");        
        }
    }

    // Returns: 0 if the number is invalid
    function test_number($number) {
        if (is_numeric(test_input($number))) { 
            return $number; }
        else {
            $number = 0; }
    }

    // Generic all-date validation function.
    // WARNING: Gaurd this with an exception handler. Trim can explode with
    // bad input.
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