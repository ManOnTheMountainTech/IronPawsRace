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

  require_once(plugin_dir_path(__FILE__) . 'includes/wp-defs.php');
  require_once(plugin_dir_path(__FILE__) . 'includes/debug.php');
  require_once(plugin_dir_path(__FILE__) . 'woo-connect.php');

  function do_shortcode_fetch_teams() {  
    session_start(); 

    $results;
    $woocommerce = create_wc();

    if (isset($_SESSION[WC_ORDER_ID])) {
      $wcOrderId = $_SESSION[WC_ORDER_ID] = test_number($_GET[WC_ORDER_ID]);

      // We're being called after payment for a race. Ask WooCommerce the details.

      $results = $woocommerce->get('orders/' . $order_id);
      if (NULL == $results) {
        return "Unable to talk to WooCommerce while fetching the mushers teams";
      }

      // TODO: May need to change to get the customer Id, then first and last
      // name
      $wc_billing = $results['billing'];
      $musher = $wc_billing['first_name'] . 
        ' ' . 
        $wc_billing['last_name'];

      $_SESSION[WC_CUSTOMER_ID] = $results['customer_id'];
    } else { // Ask WooCommerce if they have registered.

      $params;

      if (isset($_GET[EMAIL])) {
        $email = $_SESSION[EMAIL] = sanitize_text_field($_GET[EMAIL]);
        $params = array('email' => $email, 'role' => 'all');
      }
      else {
        if (isset($_GET[FIRST_NAME]) && isset($_GET[LAST_NAME])) {
          $first_name = $_SESSION[FIRST_NAME] = sanitize_text_field($_GET[FIRST_NAME]);
          $last_name = $_SESSION[LAST_NAME] = sanitize_text_field($_GET[LAST_NAME]);
          $params = array('first_name' => $first_name, 
          'last_name' => $last_name, 
            'role' => 'all');
        } else {
          return "Neither an email, nor a first or last name could be processed. Please try again.";
        }

        $musher = $first_name . ' ' . $last_name;
      }

      $results = $woocommerce->get(CUSTOMERS, $params);
      if (null == $results) {
        return "A musher has to be registered to use Iron Paws. Please register." . print_r($params, true);
      }

      foreach($results as $personal_info) {
        $_SESSION[WC_CUSTOMER_ID] = $personal_info->id;

        // If using e-mail for lookup, we will not have a name
        // TODO: Handle multiple addresses for one person. WooCommerce just uses
        // a custome POST action.
        if (!isset($musher)) {
          $musher = $personal_info->first_name . ' ' . $personal_info->last_name;
        }

        //return print_r($results, true);
      }
    } // end: get info from WooCommerce

    return get_mushers_team(MushDB::connect(), $musher);
  }

  function get_mushers_team(PDO $db, $person) { 
    //$teams_path = plugins_url('modify_teams.php', __FILE__);

    // TODO: Change to 
    $teams_path = plugins_url('add-a-team', __FILE__);
    $teams_selections_html = '<form method="get" id="' . TEAM . '" action="' 
    . $teams_path . '">';

    // TODO: Handle both add a team and modify a team.
    $teams_selections_html .= <<<'GET_TEAMS'
          <label for="teams">Please select a team to race:</label>
          <select name="teams" id="teams">
      GET_TEAMS;

    $execSql = "CALL sp_getMushersTeams (:person)";
    //$person .= '"' + $person + '"';
    
    try { 
      $stmt = $db->prepare($execSql);
      $stmt->execute([ 'person' => $person ]);

      while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
        $teams_selections_html .= '<option value="' . $row[0] . '">' . $row[0] . '</option>';
      }

      $stmt = null; 
    }
    catch(PDOException $e) { 
      return ( 'The database returned an error while finding teams for dogs.');
      write_log(__FUNCTION__ . ': produced exception {$e}');
    }
    finally {
      $teams_selections_html .= '</select><br><br><input type="submit" value="Go"></form>';
    }
    
    return $teams_selections_html;
  }
?>