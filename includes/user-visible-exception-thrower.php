<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class User_Visible_Exception_Thrower extends \Exception {
        // @param: $errorCore -> The user-viewed error message to display
        // @param: $instance -> The instanceof this exception. Seperates out multiple throws
        //  in the log file.
        // @param: $e -> Previous exception to use.
        // @throws: User_Visible_Exception_Thrower
        static public function throwErrorCoreException(string $errorCore, int $code = 0, \Throwable $previous = null) {
            $e_ref = bin2hex(openssl_random_pseudo_bytes(16));

            // No previous exception? Let's make one!
            if (is_null($previous)) {
                $e = new \IronPaws\User_Visible_Exception_Thrower($errorCore, $code);
            }

            $e->{"userHTMLMessage"} = $errorCore;
            $e->{"instance"} = $e_ref;
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