<?php
     namespace IronPaws;

     defined( 'ABSPATH' ) || exit;

     const non_web_php = '/home/bryany/php/';
                                      // sent out
     const COMPLETED = 'completed';

     class Dogs {
          const NAME = 'dogNames';
          const AGE = "dogAge";
          const OWNER_FIRST_NAME = "dogOwnerFirstName";
          const OWNER_LAST_NAME = "dogOwnerLastName";
          const OWNER_EMAIL = "dogOwnerEmail";
          const OWNER_USER_NAME = "dogOwnerUserName";
          const OWNER_PERSON_ID = "dogOwnerPersonId";
     }
     
     const EMAIL = 'email';
     const FIRST_NAME = 'first_name';

     const GET = 'GET';
     const FORM_INCOMPLETE_ERROR = -1;

     const HIDDEN = "hidden";
     const KEY_ARG = 'key';
     const LANGUAGE = 'language';
     const LAST_NAME = 'last_name';
     const ORDERS = 'orders';
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
     const TEAM_ARGS = 'team_args';
     const TEAM_REGISTRATION = 'team-registration';
     const URI_PREFIX = "https://ironpawsllc.com/";
     const WC_CUSTOMER_ID = 'wc_customer_id';
     const WC_ORDER_ID = 'wc_order_id'; // The Woo Commerce order ID
     const WC_PRODUCT_ID = 'wc_product_id';

     // Product Id <delim> Order Id
     const WC_PAIR_ARGS = 'wc_pair_args';

     class WP_Defs {
          // TODO: See if still relevant, since no text search hits.
          const GENERIC_INVALID_PARAMETER_MSG = "An invalid query string were supplied.";
          const FORM_INCOMPLETE_MSG = "Not enough information entered.";
     }
?>