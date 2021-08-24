<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once 'wp-defs.php';
    require_once 'debug.php';
    require_once 'util.php';
    require plugin_dir_path(__FILE__) . 'vendor/autoload.php';

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;

    class Race_Registration_Exception extends \Exception {
        const RACE_CLOSED_MSG = "The race is closed. No changes can be made";
        const RACE_CLOSED_ERROR = -1;
        const PAYMENT_NOT_COMPLETED_MSG = "The payment for the race has not been completed. It's current status is %s";
        const PAYMENT_NOT_COMPLETED_ERROR = -2;
        const NO_SUCH_PERSON_ERROR = -3;
        const CANT_GET_MUSHERS_TEAMS_MSG = "Unable to talk to WooCommerce while fetching the mushers teams.";
        const CANT_GET_MUSHERS_TEAMS_ERROR = -4;
        const CANT_GET_INFO_FROM_ORDER_MSG = "Can't get information about the musher from the order";
        const CANT_GET_INFO_FROM_ORDER_ERROR = -5;

        public function __construct($string) {
            if (is_wp_debug() && isset($e->xdebug_message)) {
                $this->$message = $this->message . '\n' . $e->xdebug_message;
            }

            parent::__construct($string);
        }

        static function throwPaymentNotCompleted($wc_rest_result_orders) {
            throw new Race_Registration_Exception(
                sprintf(PAYMENT_NOT_COMPLETED_MSG, 
                    $wc_rest_result_orders),
                PAYMENT_NOT_COMPLETED_ERROR);
        }

        function processRaceAccessCase() {
            switch($this->getCode()) {
                case self::RACE_CLOSED_ERROR:
                    return '<p>' . RACE_CLOSED_MSG . '</p>';
        
                case self::PAYMENT_NOT_COMPLETED_ERROR:
                    return '<p>' . $this->getMesssage() . '</p>';
            }
        }

        public string $error_html;
    }


    /* Order is done, let's move on */

?>