<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class User_Visible_Exception_Thrower extends \Exception {
        const USER_HTML_MESSAGE = "userHTMLMessage";

        static function makeId() {
            return bin2hex(openssl_random_pseudo_bytes(16));
        }

        // @param: $errorCore -> The user-viewed error message to display
        // @param: $instance -> The instanceof this exception. Seperates out multiple throws
        //  in the log file.
        // @param: $e -> Previous exception to use.
        // @throws: User_Visible_Exception_Thrower
        static public function throwErrorCoreException(string $errorCore, int $code = 0, \Throwable $e = null) {
            $e_ref = self::makeId();

            // No previous exception? Let's make one!
            if (is_null($e)) {
                $e = new \IronPaws\User_Visible_Exception_Thrower($errorCore, $code);
            }

            $e->{"userHTMLMessage"} = $errorCore;
            $e->{"instance"} = $e_ref;
            throw $e;
        }
        
        static public function getUserMessage(\Throwable $e) {
            $message = (property_exists($e, self::USER_HTML_MESSAGE))? 
                $e->userHTMLMessage . " {" . $e->instance . '}' : 
                "An error occured. Id=" . self::makeId();
            
            var_debug($e);
            write_log($message . '\n' . print_r($e));

            return $message;     
        }
    }
?>