<?php
    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
    require plugin_dir_path(__FILE__) . 'vendor/autoload.php';

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;
    
    class WCRaceRegistrationException extends Exception {
        const RACE_CLOSED_MSG = "The race is closed. No changes can be made";
        const RACE_CLOSED_ERROR = -1;
        const PAYMENT_NOT_COMPLETED_MSG = "The payment for the race has not been completed. It's current status is %s";
        const PAYMENT_NOT_COMPLETED_ERROR = -2;
        const NO_SUCH_PERSON_ERROR = -3;
        const CANT_GET_MUSHERS_TEAMS_MSG = "Unable to talk to WooCommerce while fetching the mushers teams.";
        const CANT_GET_MUSHERS_TEAMS_ERROR = -4;
        const CANT_GET_INFO_FROM_ORDER_MSG = "Can't get information about the musher from the order";
        const CANT_GET_INFO_FROM_ORDER_ERROR = -5;

        static function throwPaymentNotCompleted($wc_rest_result_orders) {
            throw new WCRaceRegistrationException(
                sprintf(PAYMENT_NOT_COMPLETED_MSG, 
                    $wc_rest_result_orders),
                PAYMENT_NOT_COMPLETED_ERROR);
        }
    }

    // Checks to see if edits to the race are allowed.
    // @param: $wc_rest_result: the result of a WooCommerce /orders 
    //  REST api V3 call.
    // @throws: WCRaceRegistrationException on failure.
    function checkRaceEditable($wc_rest_result_orders) {
        switch ($wc_rest_result_orders->status) {
            case 'processing':
                return;
            case 'completed':
                throw new WCRaceRegistrationException(RACE_CLOSED_MSG, RACE_CLOSED_ERROR);
            default:
                return new WCRaceRegistrationException(PAYMENT_NOT_COMPLETED_MSG, 
                    PAYMENT_NOT_COMPLETED_ERROR);
        }
    }

    function create_wc() {
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
        return $woocommerce->http->getResponse()->getBody();
    }

    // @function processResponse
    // @param $result - result from a call to the WooCommerce REST API
    // @returns : null = failure, associative object = success
    // ----
    // Internally, Client calls HttpClient, which sets the parameters as CURL
    // options, and then calls CURL. The result is processed by json_decode.
    // json_decode can return true, false, or null. However, the body still
    // may contain valid data. Try a little harder to get it.
    function processResponse($result) {
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

    /* Order is done, let's move on */

?>