<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class Rest_API_Exception extends \Exception {
        static public function throwErrorCoreException(string $errorCore, int $instance) {
            $this->message = "Creating the $errorCore was unsuccessful[{$instance}]";
            $this->code = 0;
            throw $this;
        }
    }
?>