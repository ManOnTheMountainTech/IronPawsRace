<?php
    // also refer to <wordpress>_wpip_wc_customer_lookup
  namespace IronPawsLLC;

  defined( 'ABSPATH' ) || exit;

  use Automattic\WooCommerce\HttpClient\HttpClientException;
  
  require_once plugin_dir_path(__FILE__) . '../includes/wp-defs.php';
  require_once plugin_dir_path(__FILE__) . '../includes/debug.php';
  require_once plugin_dir_path(__FILE__) . '../includes/wc-rest.php';
  require_once plugin_dir_path(__FILE__) . 'sprocs-tests.php';
  require_once plugin_dir_path(__FILE__) . 'wc-customers.php';
  require_once plugin_dir_path(__FILE__) . '../includes/users.php';

  function do_shortcode_run_tests() {
    $sprocs_tests = new Sprocs_Tests();
    $wc_customers = new WC_Customers();

    $result = "";

    try {

      $result .= Users::get(Users::KEY_FIRST_NAME, "Bryan");
      $result .= Users::get(Users::KEY_FIRST_NAME, "Invalid");
      $result .= Users::get(Users::KEY_FIRST_NAME, "Karen");
      
      $lookup = Users::lookup(null, "Bryan", "Young");
      var_debug($lookup);

      return $result;
    } catch (HttpClientException $e) {
      $result = $wc_customers->result;
      $result .= "Caught. Message: " . $e->getMessage(); // Error message.
      $result .= " Request:" . $e->getRequest(); // Last request data.
      $result .= " Response:" . $e->getResponse(); // Last response data.
      return $result;
    }

    return "Forgot to return the test result";
  }

  /*
  function do_shortcode_test_sp_updateTRSEForRSE() {
    $db = new Mush_DB();
                        
    $modified_columns = $db->execAndReturnColumn(
      "call sp_updateTRSEForRSE(:wcOrderId, :mileage, :outcome, :raceElapsedTime, :raceStage, :dateCreated)",
      ['wcOrderId' => 64, 
      'mileage' => 12, 
      'outcome' => 'dropped', 
      'raceElapsedTime' => '01:02:04.2', 
      'raceStage' => 1, 
      'dateCreated' => date("Y-m-d H:i:s")],
      "A failure occured writing this team race stage entry.");

      return print_r($modified_columns, true);
  }*/
?>