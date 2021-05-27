<?php
    // also refer to <wordpress>_wpip_wc_customer_lookup
    defined( 'ABSPATH' ) || exit;

    namespace IronPaws;
  
    require_once plugin_dir_path(__FILE__) . '../includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . '../includes/debug.php';
    require_once plugin_dir_path(__FILE__) . '../wc-rest.php';
    require_once plugin_dir_path(__FILE__) . 'sprocs-tests.php';
    require_once plugin_dir_path(__FILE__) . 'wc-customers.php';

  function do_shortcode_run_tests() {
    $sprocs_tests = new Sprocs_Tests();
    $wc_customers = new WC_Customers();

    try {
      //log_by_name();
      //$wc_customers->log_by_roll();
      //$wc_customers->log_by_name();
      //$wc_customers->log_customer_by_id(4);
      $wc_customers->log_order_by_id(54);
      return $wc_customers->result;
    } catch (HttpClientException $e) {
      $result = $wc_customers->result;
      $result .= "Caught. Message: " . $e->getMessage(); // Error message.
      $result .= " Request:" . $e->getRequest(); // Last request data.
      $result .= " Response:" . $e->getResponse(); // Last response data.
      return $result;
    }

    return "Forgot to return the test result";
  }
?>