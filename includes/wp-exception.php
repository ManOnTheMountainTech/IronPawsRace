<?php
    namespace IronPawsLLC;

use WP_Error;

    defined( 'ABSPATH' ) || exit;

    class WP_Exception extends \Exception {
        public function __construct(public WP_Error  $error) {
            parent::__construct($error->get_error_message(), 
                $error->get_error_code());
        }
    }
?>