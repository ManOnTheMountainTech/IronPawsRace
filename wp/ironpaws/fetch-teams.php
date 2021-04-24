<?php
  // Load wordpress regardless of where it is located. Remember, it could be
  // in any subfolder.
  defined( 'ABSPATH' ) || exit;
  session_start(); 

  const NO_SUCH_PERSON_ERROR = "A musher has to be registered to use Iron Paws. Please register.";

  require_once(plugin_dir_path(__FILE__) . 'includes/wp-defs.php');
  require_once(plugin_dir_path(__FILE__) . 'includes/debug.php');
  require_once(plugin_dir_path(__FILE__) . 'woo-connect.php');

  // Fetch's a musher's teams.
  // @param: optional: _GET[WC_ORDER_ID] -> WooCommerce order ID
  function do_shortcode_fetch_teams() { 

    $teams;

    try {
      $musher = get_musher();
      if (NO_SUCH_PERSON_ERROR == $musher) { 
        $dude = '';

        if (array_key_exists(FIRST_NAME, $_GET)) {
          $dude = $_GET[FIRST_NAME] . ' ';
        }

        if (array_key_exists(LAST_NAME, $_GET)) {
          $dude .= $_GET[LAST_NAME] . ' ';
        }

        if (array_key_exists(EMAIL, $_GET)) {
          $dude .= '(' . $_GET[EMAIL] . ')';
        }

        unset_get_vars();
        
        return <<<PLEASE_REGISTER
          <p>Musher $dude appears to not have registered. 
          Please register here: <a href="race-registration">Race registration</a></p>
        PLEASE_REGISTER;
      }

      $teams = get_mushers_teams(new MushDB(), $musher);
    }
    catch(PDOException $e) {
      statement_log(__FUNCTION__, __LINE__, "Unable to create db object", $e);
      return "Unable to connect to the database. Please try again later.";
    }

    if (null == $teams) return <<<ONLY_REGISTER
      No dog teams found.</br>
      <a href="register-a-new-team">Register a new team</a>
    ONLY_REGISTER;
  }

  // Gets the musher's name from a woo commercre order id or email.
  // @param: WooCommerce order ID, else
  // @param: _GET[EMAIL], else
  // @param: _GET[FIRST_NAME]
  // @param: _GET[LAST_NAME]
  // @returns: the musher's name if found, else NO_SUCH_PERSON_ERROR
  function get_musher() {  
    $results;
    $woocommerce = create_wc();
    $wcOrderId;

    // If we have a WooCommerce order ID, then get the musher's name from the
    // WooCommerce order
    if (array_key_exists(WC_ORDER_ID, $_GET)) {
      $wcOrderId = $_SESSION[WC_ORDER_ID] = test_number($_GET[WC_ORDER_ID]);

      if ($wcOrderId > 0) {
        // We're being called after payment for a race. Ask WooCommerce the details.

        try {
          $results = $woocommerce->get('orders/' . $order_id);
          if (NULL == $results) {
            unset_get_vars();
            return "Unable to talk to WooCommerce while fetching the mushers teams.";
          }
        }
        catch (HttpClientException $e) {
          write_log(__FUNCTION__ . __LINE__ . "Caught. Message:", $e->getMessage() ); // Error message.
          write_log(" Request:", $e->getRequest() ); // Last request data.
          write_log(" Response:", $e->getResponse() ); // Last response data.
          return "Failed to get the information about the order";
        }

        // TODO: May need to change to get the customer Id, then first and last
        // name
        $wc_billing = $results['billing'];
        $musher = $wc_billing['first_name'] . 
          ' ' . 
          $wc_billing['last_name'];

        $_SESSION[WC_CUSTOMER_ID] = $results['customer_id'];
      }
    } 
    
    // case: WoocCommerce order ID not passed in
    if (!isset($wcOrderId))
    { // Ask WooCommerce if they have registered.
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
          unset_get_vars();
          return "Neither an email, nor a first or last name could be processed. Please try again.";
        }

        $musher = $first_name . ' ' . $last_name;
      }

      write_log(__FUNCTION__ . "->params: " , $params);
      try {
        $results = $woocommerce->get(CUSTOMERS, $params);
        if (null == $results) {
          return NO_SUCH_PERSON_ERROR;
        }
      }
      catch (HttpClientException $e) {
        write_log(__FUNCTION__ . __LINE__ . "Caught. Message:", $e->getMessage() ); // Error message.
        write_log(" Request:", $e->getRequest() ); // Last request data.
        write_log(" Response:", $e->getResponse() ); // Last response data.
        return NO_SUCH_PERSON_ERROR;
      }

      // Get the WooCommerce customer ID from the retured customer information.
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

    unset_get_vars();

    return $musher;
  }

  // Remember that unset checks for null also. So unset everything for when
  // we call ourselves again.
  function unset_get_vars() {
    unset($_GET[EMAIL]);
    unset($_GET[FIRST_NAME]);
    unset($_GET[LAST_NAME]);  
  }

  // @params: 
  // @returns: an HTML select table of the mushers's teams. null on failure.
  function get_mushers_teams(MushDB $db, string $person) { 
    //$teams_path = plugins_url('modify_teams.php', __FILE__);

    // TODO: Change to 
    $teams_path = plugins_url('add-a-team', __FILE__);
    $teams_selections_html = '<form method="get" id="' . TEAM_NAME . '" action="' 
      . $teams_path . '">';

    // TODO: Handle both add a team and modify a team.
    $teams_selections_html .= <<<'GET_TEAMS'
          <label for="teams">Please select a team to race:</label>
          <select name="teams" id="teams">
      GET_TEAMS;

    $execSql = "CALL sp_getMushersTeams (?)";
    //$person .= '"' + $person + '"';

    log_if_key_set($_SESSION, WC_CUSTOMER_ID, "Customer Id = ");
    
    try { 
      $stmt = $db->query($execSql, [$_SESSION[WC_CUSTOMER_ID]]);
      $foundATeam = false;

      while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {

        // remember: $TEAMS for add-a-team must be an index.
        $teams_selections_html .= '<option value="' . $row[0] . '">' . $row[1] . '</option>';
        $foundATeam = true;
      }

      $stmt->closeCursor();
      $stmt = null; 
      
      if (!$foundATeam) {
        return null;
      }
    }
    catch(PDOException $e) { 
      statement_log(__FUNCTION__ , __LINE__ , ': produced exception', $e);
      return ( 'The database returned an error while finding teams for dogs.');
    }
    
    $teams_selections_html .= '</select><br><br><button type="submit" value="Register team"></form>';
    $teams_selections_html .= '<button type="submit" formaction="register-a-new-team">Register a new team</button>';
    
    return $teams_selections_html;
  }
?>