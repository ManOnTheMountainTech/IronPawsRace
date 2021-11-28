<?php

    namespace IronPaws;

use PDOException;

defined( 'ABSPATH' ) || exit;

    require_once 'mysql.php';
    require_once 'user-visible-exception-thrower.php';
    require_once  plugin_dir_path(__FILE__) . '../settings/db.php';
    require_once  plugin_dir_path(__FILE__) . 'debug.php';

    class Mush_DB {
        public const OP_AUTO_EXEC = 0;
        public const OP_MANUAL_EXEC = 1;

        public const EXEC_EXCEPTION_NULL = 1;
        public const EXEC_EXCEPTION_ZERO = 2;
        public const EXEC_EXCEPTION_EMPTY = 3;
        public const EXEC_EXCEPTION_COLUMNS_MISMATCH = 4;

        protected $maxReconnectTries = 100;

        protected $reconnectTries = 0;

        public ?\PDOStatement $statement = null;

        public function getReconnectTries() {
            return $this->reconnectTries;
        }

        protected ?\PDO $conn = null;

        public function getConnection(): \PDO {
            return $this->conn;
        }

        public $perf;

        public function __construct() {
            if (MEASURE_PERF) {
                $this->perf = new Perf();
            } else {
                $this->perf = null;
            }
        }

        public function disconnect() {
            $this->conn = null;
        }

        public function connect() {
            $this->conn = new \PDO("mysql:host=" . DB::SERVERNAME . ";dbname=" . DB::DBNAME, 
                DB::USERNAME, DB::PASSWORD);
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        }

        public function execAndReturnRaw(string $statement, 
            array $params = [],
            string $errorCore) {

            $stmt = $this->execSql($statement, $params);

            if (is_null($stmt)) {
                global $error_instance;
                User_Visible_Exception_Thrower::throwErrorCoreException(
                    $errorCore, self::EXEC_EXCEPTION_NULL);
            }

            $row = $stmt->fetchAll(\PDO::FETCH_NUM);
            $stmt->closeCursor();

        return $row;
    }

        // @param $errorCore The "core" of the error message to display.
        // @returns -> the column from the database
        public function execAndReturnRow(string $statement, 
            array $params = [],
            string $errorCore,
            int $numExpectedColumns) {

            $row = $this->execAndReturnRaw($statement,
                $params,
                $errorCore,
                $numExpectedColumns);

            if (empty($row)) {
                User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, self::EXEC_EXCEPTION_EMPTY);
            }

            $row = $row[0];

            if (count($row) != $numExpectedColumns) {
                User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, self::EXEC_EXCEPTION_COLUMNS_MISMATCH);
            }

            return $row;
        }

        public function execAndFetchAll(string $statement, 
            array $params = [],
            string $errorCore) {
                $stmt = $this->execSql($statement, $params);

                if (is_null($stmt)) {
                    User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, self::EXEC_EXCEPTION_NULL);
                }
    
                // PDO::lastInsertId() may have issues with stored procedures
                // https://stackoverflow.com/questions/15562478/php-mysql-pdo-lastinsertid-is-returning-0-when-using-a-procedure-to-insert-rows
                $rawId = $stmt->fetchAll(\PDO::FETCH_NUM);
                $stmt->closeCursor();

                if (0 == $rawId) {
                    User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, self::EXEC_EXCEPTION_ZERO);
                }
    
                if (empty($rawId)) {
                    User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, self::EXEC_EXCEPTION_EMPTY);     
                }

                // Result always comes back in the array of the array for single object returns.
                $id = $rawId[0][0];

                return $id;
            }

            // Returns null when empty on return
            public function execAndFetchAllOrNull(string $statement, 
            array $params = [],
            string $errorCore) {
                $stmt = $this->execSql($statement, $params);

                if (is_null($stmt)) {
                    User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, self::EXEC_EXCEPTION_NULL);
                }
    
                // PDO::lastInsertId() may have issues with stored procedures
                // https://stackoverflow.com/questions/15562478/php-mysql-pdo-lastinsertid-is-returning-0-when-using-a-procedure-to-insert-rows
                $rawId = $stmt->fetchAll(\PDO::FETCH_NUM);
                $stmt->closeCursor();

                if (0 == $rawId) {
                    User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, self::EXEC_EXCEPTION_ZERO);
                }
    
                if (empty($rawId)) {
                    return null;
                }

                // Result always comes back in the array of the array for single object returns.
                $id = $rawId[0][0];

                return $id;
            }

        // @param $errorCore The "core" of the error message to display.
        // @returns -> the returned id
        // @throws -> Enhanced exception if nothing returned
        public function execAndReturnInt(string $statement, 
            array $params = [],
            string $errorCore) {

            $id = $this->execAndFetchAll($statement, $params, $errorCore);

            if (0 == $id) {
                User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, self::EXEC_EXCEPTION_ZERO);
            }

            return $id;
        }

        // Retrieves a nullable int
        // @param $errorCore The "core" of the error message to display.
        // @returns -> the returned id
        public function execAndReturnIntOrNull(string $statement, 
            array $params = [],
            string $errorCore) {

            $id = $this->execAndFetchAllOrNull($statement, $params, $errorCore);

            if (is_null($id)) {
                return $id;
            }

            if (0 == $id) {
                User_Visible_Exception_Thrower::throwErrorCoreException($errorCore, self::EXEC_EXCEPTION_ZERO);
            }

            return $id;
        }

        // Discourge calling these without the protection of he auto-retry loop.
        // @param: sqlWithPlaceholders
        // @param: A 1-dimensional array of the values for the placeholders.
        // @return: The PDOStatement on success, null on failure.
        protected function preparedExec(string $sqlWithPlaceholders, array $params = []) {
            // Turn off the display of errors so we don't see packets
            // out of order. We allready deal with that.
            @$this->statement = $this->conn->prepare($sqlWithPlaceholders);
            if (@$this->statement->execute($params)) {
                return $this->statement;
            }
            else {
                return null;
            }
        }

        public function test($anonymous_function) {
            echo $anonymous_function();
        }        

        // @return: an array of the values from the query.
        protected function query(string $sql) {
            return @$this->conn->query($sql);
        }

        // Executes a function that either returns a result set or an exception.
        // @return: a result set
        public function execFn($fn) {
            $result_set = null;

            if (!is_null($this->perf)) {
                $this->perf->startTiming();
            }

            // ensure that everything is gaurded
            while($this->reconnectTries < $this->maxReconnectTries) {                   
                if (is_null($this->conn)) {
                    $this->connect();
                }

                $result_set = @$fn();

                if (!is_null($this->perf)) {
                    echo $this->perf->returnStats($fn); }
                $this->reconnectTries = 0;
                return $result_set;
                // Retry case
                // higher-level exception handlers will catch more specific exceptions.
                // So catch here so that the retry case works.

                if ($result_set instanceof \PDOException) {
                    if (TRACE_SQL_RETRIES) {
                        echo "PDO: Error {$result_set->errorInfo[1]}<br>";
                    }

                    if (in_array($result_set->errorInfo[1], MySql::$reconnectErrors)) {
                            $this->conn = null;
                            ++$this->reconnectTries;
                            continue; 
                    } 
                } else {
                    global $error_instance;
                    ++$error_instance;
                    statement_log(__FUNCTION__, __LINE__, "Caught exception {$error_instance}", $result_set);
                    statement_log(__FUNCTION__, __LINE__, "function:", $fn);
                    var_debug($result_set);
                    
                    User_Visible_Exception_Thrower::throwErrorCoreException(
                        __("Something bad happened while talking to the database.", 7, $result_set));
                }  
            }

            //Out of retries. Let the user know.
            User_Visible_Exception_Thrower::throwErrorCoreException(
                "Retried {$this->maxReconnectTries} times. I couldn't make the network work.", 
                6, 
                $result_set);        
        }
        
        // Generic sql executor, but in an auto-retry loop. The operation will
        // tried up to $this->maxRecconectTries
        // based off of:
        // https://www.tobymackenzie.com/blog/2020/08/18/automatic-reconnect-pdo-connection-time-out/
        // @return-> Result set of database operation on success
        // @throws: Exception with userHTMLMessage set.
        // @return: params supplied: Returns a PDOStatement.
        //          params not supplied-> Returns a result set (array)
        public function execSql(string $statement = null, array $params = [], $op = self::OP_AUTO_EXEC) { // TODO: See if we really need an empty array.
            $result_set = null;

            if (!is_null($this->perf)) {
                $this->perf->startTiming();
            }

            // ensure that everything is gaurded
            while($this->reconnectTries < $this->maxReconnectTries) {                   
                try{
                    if (is_null($this->conn)) {
                        $this->connect();
                    }

                    switch($op) {
                        case self::OP_AUTO_EXEC:
                            $result_set = (is_null($params) || empty($params)) ? 
                                $this->query($statement) : 
                                $this->preparedExec($statement, $params);
                            break;

                        case self::OP_MANUAL_EXEC:
                            $result_set = @$this->$statement->execute();
                            break;
                    }

                    if (!is_null($this->perf)) {
                        echo $this->perf->returnStats($statement . ' ' . print_r($params)); }
                    $this->reconnectTries = 0;
                    return $result_set;
                }   // Retry case
                // higher-level exception handlers will catch more specific exceptions.
                // So catch here so that the retry case works.
                catch(\PDOException | \Exception $e) {
                    if (isset($e->errorInfo)) {
                        if (TRACE_SQL_RETRIES) {
                            echo "PDO: Error {$e->errorInfo[1]}<br>";
                        }

                        if (in_array($e->errorInfo[1], MySql::$reconnectErrors)) {
                                $this->conn = null;
                                ++$this->reconnectTries;
                                continue; 
                        } 
                    } else {
                        global $error_instance;
                        ++$error_instance;
                        statement_log(__FUNCTION__, __LINE__, "Caught exception {$error_instance}", $e);
                        statement_log(__FUNCTION__, __LINE__, "prepare:", $statement);
                        statement_log(__FUNCTION__, __LINE__, "execute:", $params);
                        var_debug($e);
                        
                        User_Visible_Exception_Thrower::throwErrorCoreException(
                            __("Something bad happened while talking to the database.", 7, $e));
                    }
                }   
            }

            

            //Out of retries. Let the user know.
            User_Visible_Exception_Thrower::throwErrorCoreException(
                "Retried {$this->maxReconnectTries} times. I couldn't make the network work.", 
                6, 
                $e);        
        }
    }

    function makeSqlString(string $value) {
        return '\'' . $value . '\'';
    }

?>