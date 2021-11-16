<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class User_Visible_Exception_Thrower extends \Exception {
        const USER_HTML_MESSAGE = "userHTMLMessage";
        const INSTANCE = "instance";

        static function makeId() {
            return bin2hex(openssl_random_pseudo_bytes(8));
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
        // Returns the instance of the exception.
        static public function getInstance(\Throwable $e) {
            if (property_exists($e, self::INSTANCE)) {
                return $e->{"instance"};
            }

            return null;
        }
        
        static public function getUserMessage(\Throwable $e) {
            $message = "";

            if (property_exists($e, self::USER_HTML_MESSAGE)) 
                $message .= $e->userHTMLMessage . "<br>";
            
            if (property_exists($e, self::INSTANCE)) {
                $message .= __(" (Instance: ") . $e->instance . ")";
            }

            $data = \get_plugin_data("..\ironpaws.php");
            $message .= __(" Version=") . $data["Version"];
    
            if (empty($message)) {
                $message .= "An error occured. Id=" . self::makeId();
            }
            
            var_debug($e);
            write_log($message . '\n' . print_r($e));

            while(!is_null($e->getPrevious())) {
                $e = $e->getPrevious();
                var_debug($e);
                write_log($message . '\n' . print_r($e));
            }

            return $message;     
        }
    }
?>