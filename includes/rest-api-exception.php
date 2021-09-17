<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class Rest_API_Exception extends \Exception {
        static public function throwErrorCoreException(string $errorCore, int $instance) {
            $that = new Rest_API_Exception($errorCore, $instance);

            $that->message = "Creating the $errorCore was unsuccessful[{$instance}]";
            $that->code = 0;
            throw $that;
        }
    }
?>