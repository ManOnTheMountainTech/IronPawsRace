<?php

    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once 'mysql.php';
    require_once 'user-visible-exception-thrower.php';
    require_once  plugin_dir_path(__FILE__) . '../settings/db.php';
    require_once  plugin_dir_path(__FILE__) . 'debug.php';

    class Mush_DB {
        protected $maxReconnectTries = 100;

        protected $reconnectTries = 0;

        public function getReconnectTries() {
            return $this->reconnectTries;
        }

        protected $reconnectDelay = 400; // in ms

        protected $conn = null;

        public $nanoTimeStart = 0;
        public $nanoTimeStop = 0;

        public function disconnect() {
            $this->conn = null;
        }

        public function connect() {
            $this->nanoTimeStart = hrtime(true);
            $this->conn = new \PDO("mysql:host=" . DB::SERVERNAME . ";dbname=" . DB::DBNAME, 
                DB::USERNAME, DB::PASSWORD);
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $this->nanoTimeStop = hrtime(true);
        }

        // @param $errorCore The "core" of the error message to display.
        // @returns -> the column from the database
        public function execAndReturnColumn(string $statement, 
            array $params = [],
            string $errorCore) {

            $stmt = $this->execSql($statement, $params);

            if (is_null($stmt)) {
                global $error_instance;
                User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, ++$error_instance);
            }

            $column = $stmt->fetchAll(\PDO::FETCH_NUM);
            $stmt->closeCursor();

            if (is_null($column)) {
                User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, 2);
            }

            return $column;
        }

        public function execAndFetchAll(string $statement, 
            array $params = [],
            string $errorCore) {
                $stmt = $this->execSql($statement, $params);

                if (is_null($stmt)) {
                    User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, 0);
                }
    
                // PDO::lastInsertId() may have issues with stored procedures
                // https://stackoverflow.com/questions/15562478/php-mysql-pdo-lastinsertid-is-returning-0-when-using-a-procedure-to-insert-rows
                $rawId = $stmt->fetchAll(\PDO::FETCH_NUM);
                $stmt->closeCursor();

                if (0 == $rawId) {
                    User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, 1);
                }
    
                if (empty($rawId)) {
                    User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, 2);     
                }

                // Result always comes back in the array of the array for single object returns.
                $id = $rawId[0][0];

                return $id;
            }

        // @param $errorCore The "core" of the error message to display.
        // @returns -> the returned id
        public function execAndReturnInt(string $statement, 
            array $params = [],
            string $errorCore) {

            $id = $this->execAndFetchAll($statement, $params, $errorCore);

            if (0 == $id) {
                User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, 3);
            }

            return $id;
        }

        // Discourge calling these without the protection of he auto-retry loop.
        protected function preparedExec(string $statement, array $params = []) {
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

        protected function query(string $statement) {
            return @$this->conn->query($statement);
        }
        
        // based off of:
        // https://www.tobymackenzie.com/blog/2020/08/18/automatic-reconnect-pdo-connection-time-out/
        // @return-> Result set of database operation on success
        // @throws: Exception with userHTMLMessage set.
        public function execSql(string $statement, array $params = []) { // TODO: See if we really need an empty array.
            $this->reconnectTries = 0;
            $this->nanoTimeStart = hrtime(true);

            while($this->reconnectTries < $this->maxReconnectTries) {    
                try{
                    // allows for retries based on connection errors
                    if (null == $this->conn) {
                        $this->connect();
                    }
                    $result_set = (is_null($params) || empty($params)) ? $this->query($statement) : $this->preparedExec($statement, $params);
                    $this->nanoTimeStop = hrtime(true);
                    return $result_set;
                }   // Retry case
                // higher-level exception handlers will catch more specific exceptions.
                // So catch here so that the retry case works.
                catch(\PDOException | \Exception $e) {
                    if (isset($e->errorInfo) && 
                        (in_array($e->errorInfo[1], MySql::$reconnectErrors))) {
                            $this->conn = null;
                            usleep($this->reconnectDelay * 1000);
                            ++$this->reconnectTries;
                            continue; 
                    
                    } else {
                        global $error_instance;
                        ++$error_instance;
                        statement_log(__FUNCTION__, __LINE__, "Caught exception {$error_instance}", $e);
                        statement_log(__FUNCTION__, __LINE__, "prepare:", $statement);
                        statement_log(__FUNCTION__, __LINE__, "execute:", $params);

                        if (is_wp_debug()) {
                            var_dump($e);
                                return "Unhandled MySQL exception";
                        }

                        global $error_instance;

                        // TODO
                        throw User_Visible_Exception_Thrower::throwErrorCoreException(
                            "Encountered a network error that cannot be retried. Out of options.", 
                            $error_instance , 
                            $e); 
                    }
                }   
            }

            //Out of retries. Let the user know.
            User_Visible_Exception_Thrower::throwErrorCoreException(
                "Retried $this->maxConnectRetries times. I couldn't make the network work.", 
                $GLOBALS['error_instance'], 
                $e);        
        }
    }

    function makeSqlString(string $value) {
        return '\'' . $value . '\'';
    }
?>