<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class Mush_DB_Exception extends \Exception {
        static public function throwErrorCoreException(string $errorCore, int $instance, \Exception $e = null) {
            if (is_null($e)) {
                $e = new \Exception();
            }

            $e->{"userHTMLMessage"} = "$errorCore [{$instance}]";
            throw $e;
        }
    }
?>