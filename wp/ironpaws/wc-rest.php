<?php
    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
    require plugin_dir_path(__FILE__) . 'vendor/autoload.php';

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;

    class WC_Rest {
        protected $woocommerce;

        public function __construct() {
            $woocommerce = WC_Rest::create_wc();
        }

        function query_race_is_editable(int $wc_order_id) {
            // We might be called directly, so don't assume set
            // cases to handle:
            //  1 -> Called immediately after purchase.
            //  2 -> Created an account, but has not purchased anything.
            //  3 -> Purchased previously and logged in
            // The user should be verified as logged in.
            // TODO: Jump back from logging in.

            // Validate the order id
            $results = $this->woocommerce->get('orders/' . $wc_order_id);
            if (NULL == $results) {
                return "Unable to talk to WooCommerce while getting customer information";
            }

            try {
                checkRaceEditable($results);
            }
            catch(WCRaceRegistrationException $e) {
                $error = $e->processRaceAccessCase();
                if (!is_null($error)) {return $error;}
            }

            return null;
        }

            
        /** 
         * @param: array $params -> 
         * @return: the customer information from woo commerce
         * @throws: WCRaceRegistrationException
         */
        function throwGetCustomersFromWoo($params) {
            $results;

            try {
            $results = $this->woocommerce->get(CUSTOMERS, $params);
            if (null == $results) {
                throw new WCRaceRegistrationException(NO_SUCH_PERSON_ERROR);
            }
            }
            catch (HttpClientException $e) {
                handleHttpClientException($e);
            throw new WCRaceRegistrationException(NO_SUCH_PERSON_ERROR);
            }

            return $results;
        }

        function throwParseFirstLastArgsGetCustomers() {
            return $this->throwGetCustomersFromWoo($this->makeWCParamsFromFirstLast());
        }
    
        // Checks to see if edits to the race are allowed.
        // @param: $wc_rest_result: the result of a WooCommerce /orders 
        //  REST api V3 call.
        // @throws: WCRaceRegistrationException on failure.
        static function checkRaceEditable($wc_rest_result_orders) {
            switch ($wc_rest_result_orders->status) {
                case PROCESSING:
                    return;
                case COMPLETED:
                    throw new WCRaceRegistrationException(RACE_CLOSED_MSG, RACE_CLOSED_ERROR);
                default:
                    throw new WCRaceRegistrationException(PAYMENT_NOT_COMPLETED_MSG, 
                        PAYMENT_NOT_COMPLETED_ERROR);
            }
        }

        static function checkRaceReadble($wc_rest_result_order) {
            switch ($wc_rest_result_orders->status) {
                case PROCESSING:
                    return;
                case COMPLETED:
                    return;
                default:
                    throw new WCRaceRegistrationException(PAYMENT_NOT_COMPLETED_MSG, 
                        PAYMENT_NOT_COMPLETED_ERROR);
            }
        }

        static function create_wc() {
            return new Client(
                'http://ironpawsllc.com', 
                'ck_f79eca540f4d74a63f85845426de32283f80f9d0', 
                'cs_056dc40407f6219fbd5705594c32460130175aa9',
                [
                    'wp_api' => true,
                    'version' => 'wc/v3'
                ]
            );
        }

        function getResponseBody($response) {
            return $this->$woocommerce->http->getResponse()->getBody();
        }

        // @function processResponse
        // @param $result - result from a call to the WooCommerce REST API
        // @returns : null = failure, associative object = success
        // ----
        // Internally, Client calls HttpClient, which sets the parameters as CURL
        // options, and then calls CURL. The result is processed by json_decode.
        // json_decode can return true, false, or null. However, the body still
        // may contain valid data. Try a little harder to get it.
        static function processResponse($result) {
            if ((false == $result) || (null == $result)) {
                $body = getResponseBody($result);
                if ((false == $body) || (null == $body)) {
                    return null;
                } else {
                    $body = json_decode($body);
                    if ((false == $body) || (null == $body)) {return null;}
                    
                    return $body;
                }
            }

            return $result;
        }

        static function handleHttpClientException(HttpClientException $ehce) {
            write_log(__FUNCTION__ . __LINE__ . "Caught. Message:", $e->getMessage() ); // Error message.
            write_log(" Request:", $e->getRequest() ); // Last request data.
            write_log(" Response:", $e->getResponse() ); // Last response data.
        }
    }

?>