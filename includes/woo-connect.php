<?php
    defined( 'ABSPATH' ) || exit;

    require_once 'wp-defs.php';
    require_once 'debug.php';
    require plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    
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
                sprintf(self::PAYMENT_NOT_COMPLETED_MSG, 
                    $wc_rest_result_orders),
                    self::PAYMENT_NOT_COMPLETED_ERROR);
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
?>