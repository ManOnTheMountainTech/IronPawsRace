<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class User_Visible_Exception_Thrower extends \Exception {
        // @param: $errorCore -> The user-viewed error message to display
        // @param: $instance -> The instanceof this exception. Seperates out multiple throws
        //  in the log file.
        // @param: $e -> Previous exception to use.
        // @throws: User_Visible_Exception_Thrower
        static public function throwErrorCoreException(string $errorCore, int $instance = 0, \Exception $e = null) {
            if (is_null($e)) {
                $e = new \IronPaws\User_Visible_Exception_Thrower();
            }

            $e->{"userHTMLMessage"} = "$errorCore [{$instance}]";
            $e->{"instance"} = $instance;
            throw $e;
        }
        
        static public function getUserMessage(\Exception $e) {
            echo __FUNCTION__;
            var_debug($e);
            write_log($e);

            return (property_exists($e, "userHTMLMessage")) ?
                $e->userHTMLMessage . " {" . $e->instance . '}':
                "An error occured.";
            }
        }
?>