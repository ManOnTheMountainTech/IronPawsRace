<?php
     namespace IronPawsLLC;

     defined( 'ABSPATH' ) || exit;

     const non_web_php = '/home/bryany/php/';
                                      // sent out
     const COMPLETED = 'completed';
     
     const EMAIL = 'email';
     const FIRST_NAME = 'first_name';

     const GET = 'GET';
     const FORM_INCOMPLETE_ERROR = -1;

     const HIDDEN = "hidden";
     const KEY_ARG = 'key';
     const LANGUAGE = 'language';
     const LAST_NAME = 'last_name';

     const PENDING = 'pending';
     const POST = 'POST';
     const PROCESSING = 'processing'; // payment received, but merchandise not
     const PRODUCT_ID = 'product_id';
     const RACE_CLASS_ID = 'race_class_id';
     const RACE_PARAMS = 'race_params';
     const RACE_RESULTS_DIR = 'race_results';
     const RACE_SELECT = 'race_select';
     const SALUTATION = 'salutation';
     const QUERY_ARG_SEPERATOR = '_';
     const TEAM_ID = 'team_id';
     const TEAM_NAME = 'team_name';
     const TEAM_NAME_ID = 'team_name_id';
     const TEAM_ARG = 'TEAM_ARG';
     const TEAM_REGISTRATION = 'team-registration';
     const URI_PREFIX = "https://ironpawsllc.com/";
     const TRSE_WC_CUSTOMER_ID = 'TRSE_WC_CUSTOMER_ID';

     const WC_ORDER_ID = 'wc_order_id'; // The Woo Commerce order ID

     // Product Id <delim> Order Id
     const WC_PAIR_ARGS = 'wc_pair_args';
     const WC_PRODUCT_ID = 'wc_product_id';

     class WP_Defs {
          const IRONPAWS_TEXTDOMAIN = 'ironpaws';

          public static $GENERIC_INVALID_PARAMETER_MSG;
          public static $FORM_INCOMPLETE_MSG;

          static function init()
          {
               self::$GENERIC_INVALID_PARAMETER_MSG = __("A parameter is invalid.");  
               self::$FORM_INCOMPLETE_MSG = __("Not enough information entered.");
          }
     }
?>