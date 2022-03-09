<?php
    namespace IronPawsLLC;

use Throwable;
use WP_REST_Response;

    defined( 'ABSPATH' ) || exit;

    class Rest_API_Exception extends REST_API_Exception_Definition {
        public function __construct(string $message, 
            public WP_REST_Response $wp_response) {
            parent::__construct($message, $wp_response->status);
        }

        function getHeaders(): mixed
        {
            return $this->wp_response->get_headers();
        }

        function getJSONResponseData(): mixed {
            return $this->wp_response->jsonSerialize();
        }
    }
?>