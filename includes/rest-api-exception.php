<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class Rest_API_Exception extends \Exception {
        static public function throwErrorCoreException(string $errorCore, int $code) {
            $that = new Rest_API_Exception($errorCore, $code);

            $that->message = "Creating the $errorCore was unsuccessful[{$code}]";
            $that->code = 0;
            throw $that;
        }
    }
?>