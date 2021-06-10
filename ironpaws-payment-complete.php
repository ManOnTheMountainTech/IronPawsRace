<?php
     defined( 'ABSPATH' ) || exit;

     namespace IronPaws;

     require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
     require_once plugin_dir_path(__FILE__) . 'wc-rest.php';

    use Automattic\WooCommerce\Admin\Overrides\Order;

    // Called at the very end of the final order screen, as part of 
    // templates/order/order-detail-customer.php
    // @args: WC_Order -> The order object
     function ironpaws_order_details_after_customer_details(Order $order) {
      if (!is_null($order)) {
        $wc_order_arg = $order->get_order_number();

        $woocommerce = new WC_Rest();

        // Make sure that the payment is complete
        if ($wc_order_arg > 0) {
          // We're being called after payment for a race. Ask WooCommerce the details.
          try {
            $results = $woocommerce->getOrdersByCustomerId($wc_order_arg);
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

          $wc_order_var = WC_ORDER_ID . '=';

          $teams_path = plugins_url("fetch-teams?{$wc_order_var}={$wc_order_arg}", __FILE__);
          echo <<<ASK_LOCATION_REGISTRATION
            <a href="$teams_path">Team registration</a>
          ASK_LOCATION_REGISTRATION;
        }
      }
    }
  ?>