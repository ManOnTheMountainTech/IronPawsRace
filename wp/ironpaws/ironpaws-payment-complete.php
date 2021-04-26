<?php
     defined( 'ABSPATH' ) || exit;
     require_once(plugin_dir_path(__FILE__) . 'includes/wp-defs.php');
     require_once(plugin_dir_path(__FILE__) . 'woo-connect.php');

     // Wordpress shortcode for payment complete.
     // When the payment complete web hook fires, have it call a display() function in this class.
     // For now stick with the order id, which is not unique to the site. 
     // The order key is unique to the site,
     // Example: https://ironpawsllc.com/checkout/order-received/48/?key=wc_order_AJyOZAdH4Xo3a
     function do_shortcode_payment_complete() {
         if (array_key_exists('key', $_GET)) {
               // Show the link to register a team
               $order_string = $_SERVER['SCRIPT_URL'];
               $url_parts = explode('/', $order_string);

               $wc_order_arg = $url_parts[3];

               $woocommerce = create_wc();

               // Make sure that the payment is complete
               if ($wc_order_arg > 0) {
                    // We're being called after payment for a race. Ask WooCommerce the details.
                    try {
                      $results = $woocommerce->get('orders/' . $wc_order_arg);
                      if (NULL == $results) {
                        return;
                      }
                    }
                    catch (HttpClientException $e) {
                      write_log(__FUNCTION__ . __LINE__ . "Caught. Message:", $e->getMessage() ); // Error message.
                      write_log(" Request:", $e->getRequest() ); // Last request data.
                      write_log(" Response:", $e->getResponse() ); // Last response data.
                      return;
                    }

                    // The order will be completed at the end of registration.
                    // It will not be possible to make any changes as a result.
                    checkRaceEditable($results);

                    $wc_order_var = WC_ORDER_ID . '=';

                    return <<<ASK_LOCATION_REGISTRATION
                      <a href="https://ironpawsllc.com/team-registration?{$wc_order_var}{$wc_order_arg}">Team registration</a>
                    ASK_LOCATION_REGISTRATION;
               }
         }
     }
?>