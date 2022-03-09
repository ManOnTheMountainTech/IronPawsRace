<?php
    namespace IronPawsLLC;

use Throwable;
use WP_REST_Response;

    defined( 'ABSPATH' ) || exit;

    class REST_API_Exception_Definition extends \Exception {
        function getHeaders(): mixed {return null;}
        function getJSONResponseData(): mixed {return null;}
    }
?>