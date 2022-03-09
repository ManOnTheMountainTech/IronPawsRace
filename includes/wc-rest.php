<?php
    namespace IronPawsLLC;

    defined( 'ABSPATH' ) || exit;

    require_once 'wp-defs.php';
    require_once 'debug.php';
    require plugin_dir_path(__FILE__) . '../settings/wc-rest-creator.php';
    require plugin_dir_path(__FILE__) . '../vendor/autoload.php';
    require_once 'autoloader.php';

    // To create a key programatically: WC_Auth::Create_Keys(), around line 209
    // Might consider WC_API_Client() in the future.

    class WC_Rest {
        protected Abstract_REST_Client $woocommerce;

        public $perf;

        const CUSTOMERS = "customers/";
        const ORDERS = 'orders';
        const CODE = 'code';
        const MESSAGE = 'message';

        public function __construct() {
            if (MEASURE_PERF) {
                $this->perf = new Perf();
                $this->perf->startTiming();
            }
            $this->woocommerce = WC_Rest_Creator::create_wc();

            if (!is_null($this->perf)) {
                echo $this->perf->returnStats("WC_Rest::construct"); }
        }

        // -Given a product id, get the product id object from the WooCommerce API.
        // @arg: product_id: int -> the product id
        // @return mixed->Returns the product object if found, otherwise null.
        // @throws-> (Implementation specific) exception: If the request fails.
        function get_product_by_id($product_id) {
            $product = $this->woocommerce->get("products/$product_id");
            if (empty($product_id)) {
                return null;
            }

            return $product;
        }

        function query_race_is_editable(int $wc_order_id) {
            // We might be called directly, so don't assume set
            // cases to handle:
            //  1 -> Called immediately after purchase.
            //  2 -> Created an account, but has not purchased anything.
            //  3 -> Purchased previously and logged in
            // The user should be verified as logged in.
            // TODO: Jump back from logging in.
            // @return: An array that contains a single dimensional array
            // of orders.

            // Validate the order id
            $results = $this->woocommerce->get(self::ORDERS . $wc_order_id);
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
        function getOrdersByCustomerId(int $TRSE_WC_CUSTOMER_ID) {
            // Validate the order id
            $results = $this->woocommerce->get(
                self::ORDERS, ['customer' => $TRSE_WC_CUSTOMER_ID]);

            return $results;
        }

        // @return-> an array of orders that are raceable (PROCESSING)
        function getAllOrders() {
            //requires view_woocommrce_reports
            // put a breakpoint on WP_REST_Server::respond_to_requests
            // or look for 'permission_callback'
            // if ( ! is_wp_error( $response ) && ! empty( $handler['permission_callback'] ) ) {
            //    $permission = call_user_func( $handler['permission_callback'], $request );
            if (!is_null($this->perf)) {
                $this->perf->startTiming();}

            // Validate the order id
            $results = $this->woocommerce->get(self::ORDERS);
            if (is_null($results)) {
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
                self::CUSTOMERS);
            if (is_null($results)) {
                return "Unable to talk to WooCommerce while getting all customers information";
            }

            return $results;
        }

        // @return: a JSON'ized WC_CUSTOMER
        function getCustomerDetailsByCustomerId(int $TRSE_WC_CUSTOMER_ID) {
            // Validate the order id
            $results = $this->woocommerce->get(
                self::CUSTOMERS . $TRSE_WC_CUSTOMER_ID);

            return $results;
        }

        function getOrderFromOrderId(int $wc_order_id) {
            return $this->woocommerce->get(self::ORDERS . '/' . $wc_order_id);
            //return $this->woocommerce->get(ORDERS,  ['id' => $wc_order_id]);
        }

        // TODO: Consider for removal
        function getProductIdsFromOrderId(int $wc_order_id) {
            
            // Validate the order id
            $results = $this->woocommerce->get(self::ORDERS . '/' . $wc_order_id);
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
            $results = null;

            $results = $this->woocommerce->get(self::CUSTOMERS, $params);
            if (is_null($results)) {
                throw new WCRaceRegistrationException(Race_Registration_Exception::NO_SUCH_PERSON_ERROR);
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
                    throw new WCRaceRegistrationException(
                        Race_Registration_Exception::RACE_CLOSED_MSG, 
                        Race_Registration_Exception::RACE_CLOSED_ERROR);
                default:
                    throw new WCRaceRegistrationException(
                        Race_Registration_Exception::PAYMENT_NOT_COMPLETED_MSG, 
                        Race_Registration_Exception::PAYMENT_NOT_COMPLETED_ERROR);
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
            switch ($wc_rest_result_order->status) {
                case 'processing':
                    return;
                case 'completed':
                    return;
                default:
                    throw new WCRaceRegistrationException(
                        Race_Registration_Exception::PAYMENT_NOT_COMPLETED_MSG, 
                        Race_Registration_Exception::PAYMENT_NOT_COMPLETED_ERROR);
            }
        }

    }

?>