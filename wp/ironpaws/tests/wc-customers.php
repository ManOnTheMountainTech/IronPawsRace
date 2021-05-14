<?php
  // Load wordpress regardless of where it is located. Remember, it could be
  // in any subfolder.
  if(!defined('ABSPATH')) {
  $pagePath = explode('/wp-content/', dirname(__FILE__));
  include_once(str_replace('wp-content/' , 
        '', 
        $pagePath[0] . 
        '/wp-load.php'));
  }

  // also refer to <wordpress>_wpip_wc_customer_lookup

  require_once(plugin_dir_path(__FILE__) . '../includes/wp-defs.php');
  require_once(plugin_dir_path(__FILE__) . '../includes/debug.php');
  require_once(plugin_dir_path(__FILE__) . '../wc-rest.php');

  function return_wc_endpoints() {
    global $woocommerce;
    return print_r($woocommerce->get(''));
  }

  function log_all_customers() {
    global $woocommerce;
    write_log($woocommerce->get(CUSTOMERS));
  }

  function log_by_roll() {
    global $woocommerce;
    $params = array('email' => 'admin@ironpaws.supermooseapps.com', 'role' => 'all');

    $result = $woocommerce->get(CUSTOMERS , $params);
    error_log(print_r($params, true));

    error_log("log_by_roll");
    write_log("Test", "123");
    write_log("test");
    write_log("params", $params);
    $body = $lastResponse = $woocommerce->http->getResponse()->getBody();
    //$body = json_decode($body, null, 512, JSON_THROW_ON_ERROR);
    return print_r($result);
  }

  function log_by_name() {
    global $woocommerce;
    $params = array('first_name' => 'John', 'last_name' => 'Doe', 'role' => 'all');
    write_log("by customer name", $woocommerce->get(CUSTOMERS, $params)); 
    $result = $woocommerce->get(CUSTOMERS , $params);
  }

  function log_customer_by_id($id) {
    global $woocommerce;
    write_log("by customer id {$id}", $woocommerce->get(CUSTOMERS, $id)); 
    dump_last_request();
  }

  function create_john_doe() {
    $data = [
      'email' => 'john.doe@example.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'username' => 'john.doe',
      'billing' => [
          'first_name' => 'John',
          'last_name' => 'Doe',
          'company' => '',
          'address_1' => '969 Market',
          'address_2' => '',
          'city' => 'San Francisco',
          'state' => 'CA',
          'postcode' => '94103',
          'country' => 'US',
          'email' => 'john.doe@example.com',
          'phone' => '(555) 555-5555'
      ],
      'shipping' => [
          'first_name' => 'John',
          'last_name' => 'Doe',
          'company' => '',
          'address_1' => '969 Market',
          'address_2' => '',
          'city' => 'San Francisco',
          'state' => 'CA',
          'postcode' => '94103',
          'country' => 'US'
      ]
    ];

    return print_r($woocommerce->post(CUSTOMERS, $data));
  }

  function wc_get_customer($search_term) {
    //$customerDS = new WC_Customer_Data_Store();
    //$customerDS->search_customers($search_term);
  }

  function do_shortcode_run_tests() {
    global $woocommerce;
    $woocommerce = create_wc();

  try {
    //log_by_name();
    log_by_roll();
    //log_customer_by_id(4);
  } catch (HttpClientException $e) {
    write_log("Caught. Message:", $e->getMessage() ); // Error message.
    write_log(" Request:", $e->getRequest() ); // Last request data.
    write_log(" Response:", $e->getResponse() ); // Last response data.
  }
    //return return_wc_endpoints();

    return "Forgot to return the test result";
  } 
?>