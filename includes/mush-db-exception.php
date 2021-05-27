<?php
    defined( 'ABSPATH' ) || exit;

    namespace IronPaws;

    class Mush_DB_Exception extends \Exception {
        static public function throwErrorCoreException(string $errorCore, int $instance) {
            $this->message = "Creating the $errorCore was unsuccessful[{$instance}]";
            $this->code = 0;
            throw $this;
        }
    }
?>