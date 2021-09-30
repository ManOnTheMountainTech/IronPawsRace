<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once 'wp-defs.php';
    require_once 'debug.php';
    require plugin_dir_path(__FILE__) . '../settings/wc-rest-creator.php';
    require plugin_dir_path(__FILE__) . '../vendor/autoload.php';
    require_once 'autoloader.php';

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;

    // To create a key programatically: WC_Auth::Create_Keys(), around line 209
    // Might consider WC_API_Client() in the future.

    class WC_Rest {
        protected $woocommerce;

        public $perf;

        const CUSTOMERS = "customers/";

        public function __construct() {
            if (MEASURE_PERF) {
                $this->perf = new Perf();
                $this->perf->startTiming();
            }
            $this->woocommerce = WC_Rest_Creator::create_wc();

            if (!is_null($this->perf)) {
                echo $this->perf->returnStats("WC_Rest::construct"); }
        }

        function query_race_is_editable(int $wc_order_id) {
            // We might be called directly, so don't assume set
            // cases to handle:
            //  1 -> Called immediately after purchase.
            //  2 -> Created an account, but has not purchased anything.
            //  3 -> Purchased previously and logged in
            // The user should be verified as logged in.
            // TODO: Jump back from logging in.
            // return: An array that contains a single dimensional array
            // of orders.

            // Validate the order id
            $results = $this->woocommerce->get(ORDERS . $wc_order_id);
            if (NULL == $results) {
                return null;
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

        // Return an array of orders that are raceable (PROCESSING)
        // https://github.com/woocommerce/wc-api-php/issues/156
        // @return: => Order(s), if there are any orders, else empty array
        function getOrdersByCustomerId(int $wc_customer_id) {
            // Validate the order id
            $results = $this->woocommerce->get(
                ORDERS, ['customer' => $wc_customer_id]);

            return $results;
        }

        // @return-> an array of orders that are raceable (PROCESSING)
        function getAllOrders() {
            if (!is_null($this->perf)) {
                $this->perf->startTiming();}

            // Validate the order id
            $results = $this->woocommerce->get(
                ORDERS);
            if (NULL == $results) {
                return "Unable to talk to WooCommerce while getting customer information";
            }

            if (!is_null($this->perf)) {
                echo $this->perf->returnStats("WC_Rest::getAllOrders");
            }

            return $results;
        }

        // @return-> a JSON'ized array of WC_CUSTOMER
        function getAllCustomers() {
            // Validate the order id
            $results = $this->woocommerce->get(
                'customers');
            if (NULL == $results) {
                return "Unable to talk to WooCommerce while getting all customers information";
            }

            return $results;
        }

        // @return: a JSON'ized WC_CUSTOMER
        function getCustomerDetailsByCustomerId(int $wc_customer_id) {
            // Validate the order id
            $results = $this->woocommerce->get(
                self::CUSTOMERS . $wc_customer_id);

            return $results;
        }

        function getOrderFromOrderId(int $wc_order_id) {
            return $this->woocommerce->get(ORDERS . '/' . $wc_order_id);
            //return $this->woocommerce->get(ORDERS,  ['id' => $wc_order_id]);
        }

        // TODO: Consider for removal
        function getProductIdsFromOrderId(int $wc_order_id) {
            
            // Validate the order id
            $results = $this->woocommerce->get(ORDERS . '/' . $wc_order_id);
            if (NULL == $results) {
                return "Unable to talk to WooCommerce while getting customer information";
            }

            // TODO: process orders in a loop. Make checkRaceEditable check if paying customer.
            if (!$this->checkRaceEditable_noThrow($results)) {return false;};

            var_debug($results);

            $line_items = $results->line_items;

            if (isset($line_items)) {
                $count = count($line_items);

                $product_ids = [$line_items->count];

                for($i = 0; $i < $count; ++$i) {
                    $product_ids[$i] = $line_items->product_id;
                }

                return $product_ids;
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

                // Checks to see if edits to the race are allowed.
        // @param: $wc_rest_result: the result of a WooCommerce /orders 
        //  REST api V3 call.
        // @returns: TRUE = race is editable. FALSE = Race is not editable.
        static function checkRaceEditable_noThrow($wc_rest_result_orders) {
            switch ($wc_rest_result_orders->status) {
                case 'processing':
                    return true;
                case 'completed':
                    return false;
                default:
                    return false;
            }
        }

        static function checkRaceReadble($wc_rest_result_order) {
            switch ($wc_rest_result_orders->status) {
                case 'processing':
                    return;
                case 'completed':
                    return;
                default:
                    throw new WCRaceRegistrationException(PAYMENT_NOT_COMPLETED_MSG, 
                        PAYMENT_NOT_COMPLETED_ERROR);
            }
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