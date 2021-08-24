<?php
  namespace IronPaws;

  defined( 'ABSPATH' ) || exit;

  // also refer to <wordpress>_wpip_wc_customer_lookup

  require_once plugin_dir_path(__FILE__) . '../includes/wp-defs.php';
  require_once plugin_dir_path(__FILE__) . '../includes/debug.php';
  require_once plugin_dir_path(__FILE__) . '../includes/autoloader.php';

  class WC_Customers {

    public sprocs_tests $sprocs_tests;

    public $result;

    function makeWCClean() {
      return WC_Rest::create_wc();
    }

    function return_wc_endpoints() {
      $this->result .= (makeWCClean()->get(''));
    }

    function log_all_customers() {
      $this->result .= (makeWCClean()->get(CUSTOMERS));
    }

    function log_by_roll() {
      $params = array('email' => 'admin@ironpawsllc.com', 'role' => 'all');

      $wcRest = $this->makeWCClean();

      $this->result .= "<p>log_by_roll<br>";
      $this->result .= "params ";
      $this->result .= pre_print($params);
      $this->result .= pre_print($wcRest->get(CUSTOMERS , $params));

      $body = $lastResponse = $wcRest->http->getResponse()->getBody();
      //$body = json_decode($body, null, 512, JSON_THROW_ON_ERROR);
    }

    function log_by_name() {
      $params = array('first_name' => 'John', 'last_name' => 'Doe', 'role' => 'all');
      $this->result .= "<p>by customer name<br>";
      $this->result .= pre_print($this->makeWCClean()->get(CUSTOMERS, $params));
    }

    function log_customer_by_id($id) {
      $this->result .= "<p>by customer id {$id}<br>";
      $this->result .= pre_print($this->makeWCClean()->get(CUSTOMERS, ['id' => $id])); 
    }

    function log_order_by_id($id) {
      $this->result .= "<p>by order id {$id}<br>";
      $this->result .= pre_print($this->makeWCClean()->get(ORDERS, ['id' => $id]));
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

      return pre_print($woocommerce->post(CUSTOMERS, $data));
    }

    function wc_get_customer($search_term) {
      //$customerDS = new WC_Customer_Data_Store();
      //$customerDS->search_customers($search_term);
    }
  } // end: class WC_Customers(
?>