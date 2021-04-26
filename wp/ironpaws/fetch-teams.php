<?php
  // Load wordpress regardless of where it is located. Remember, it could be
  // in any subfolder.
  defined( 'ABSPATH' ) || exit;
  session_start(); 

  require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
  require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
  require_once plugin_dir_path(__FILE__) . 'woo-connect.php';
  require_once plugin_dir_path(__FILE__) . 'mush-db.php';

  use Automattic\WooCommerce\Client;
  use Automattic\WooCommerce\HttpClient\HttpClientException;

  const NO_SUCH_PERSON_MSG = "Neither an email, nor a first or last name could be processed. Please try again.";
  const FORM_INCOMPLETE_MSG = "Not enough information entered.";
  const FORM_INCOMPLETE_ERROR = -1;

  $wc_customer_id;

  // Fetch's a musher's teams.
  // @param: optional: _GET[WC_ORDER_ID] -> WooCommerce order ID
  function do_shortcode_fetch_teams() { 
    try {
      $musher = get_musher();
      $teams = get_mushers_teams(new MushDB(), $musher);
      if (null == $teams) return <<<ONLY_REGISTER
        No dog teams found for $musher.</br>
        <a href="register-a-new-team">Register a new team</a>
      ONLY_REGISTER;
    }
    catch(PDOException $e) {
      statement_log(__FUNCTION__, __LINE__, "Unable to create db object", $e);
      return "Unable to connect to the database. Please try again later.";
    }
    catch(WCRaceRegistrationException $e) {
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
        var_dump($e);
        
        switch($e->getCode()) {
          case NO_SUCH_PERSON_ERROR:
            return <<<PLEASE_REGISTER
              <p>Musher $dude appears to not have registered. 
              Please register here: <a href="race-registration">Race registration</a></p>
            PLEASE_REGISTER;

          case RACE_CLOSED_ERROR:
            return '<p>' . RACE_CLOSED_MSG . '</p>';

          case PAYMENT_NOT_COMPLETED_ERROR:
            return '<p>' . $e->getMesssage() . '</p>';
        }
    } catch(Exception $e) {
      return makeHTMLErrorMessage($e->getMessage());
    }
  }

  function handleHttpClientException(HttpClientException $ehce) {
    write_log(__FUNCTION__ . __LINE__ . "Caught. Message:", $e->getMessage() ); // Error message.
    write_log(" Request:", $e->getRequest() ); // Last request data.
    write_log(" Response:", $e->getResponse() ); // Last response data.
  }

  // Processes get args to wc customer params
  // @return: The musher name in first and last format
  function makeWCParamsFromFirstLast() {
    if (isset($_GET[FIRST_NAME]) && isset($_GET[LAST_NAME])) {
      $first_name = $_SESSION[FIRST_NAME] = sanitize_text_field($_GET[FIRST_NAME]);
      $last_name = $_SESSION[LAST_NAME] = sanitize_text_field($_GET[LAST_NAME]);

      if (('' == $first_name) || ('' == $last_name)) {
        throw new Exception(FORM_INCOMPLETE_MSG, FORM_INCOMPLETE_ERROR);
      }

      $params = array('first_name' => $first_name, 
      'last_name' => $last_name, 
        'role' => 'all');
    } else {
      throw new Exception(FORM_INCOMPLETE_MSG, FORM_INCOMPLETE_ERROR);
    }

    return $params;
  }

  // @return: the customer information from woo commerce
  // @throws: WCRaceRegistrationException
  function throwGetCustomersFromWoo($params, $woocommerce) {
    $results;

    try {
      $results = $woocommerce->get(CUSTOMERS, $params);
      if (null == $results) {
        echo "throw wc";
        throw new WCRaceRegistrationException(NO_SUCH_PERSON_ERROR);
      }
    }
    catch (HttpClientException $e) {
      handleHttpClientException($e);
      echo "throw http";
      throw new WCRaceRegistrationException(NO_SUCH_PERSON_ERROR);
    }

    return $results;
  }

  function throwParseFirstLastArgsGetCustomers($woocommerce) {
    return throwGetCustomersFromWoo(makeWCParamsFromFirstLast($woocommerce), 
      $woocommerce);
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
      $wcOrderId = test_number($_GET[WC_ORDER_ID]);

      if ($wcOrderId > 0) {
        // We're being called after payment for a race. Ask WooCommerce the details.
        try {
          $results = $woocommerce->get('orders/' . $order_id);
          if (NULL == $results) {
            throw new WCRaceRegistrationException(CANT_GET_INFO_FROM_ORDER_ERROR, 
              CANT_GET_INFO_FROM_ORDER_MSG);
          }
        }
        catch (HttpClientException $e) {
          handleHttpClientException($e);
          throw new WCRaceRegistrationException(CANT_GET_INFO_FROM_ORDER_ERROR, 
            CANT_GET_INFO_FROM_ORDER_MSG);
        }

        // Verify that the race that they are registering for is paid for.
        // Exception handlers need the get vars.
        checkRaceEditable($results);

        // Now that the order id works, save it.
        $_SESSSION[WC_ORDER_ID] = $wcOrderId;

        // TODO: May need to change to get the customer Id, then first and last
        // name
        $wc_billing = $results['billing'];
        $musher = $wc_billing['first_name'] . 
          ' ' . 
          $wc_billing['last_name'];

        global $wc_customer_id;
        $wc_customer_id = $_SESSION[WC_CUSTOMER_ID] = $results['customer_id'];

        return $musher;
      }
    } 
    
    // Ask WooCommerce if they have registered.
    $params;

    // Do a query for each field individually.
    if (isset($_GET[EMAIL])) {
      $email = $_SESSION[EMAIL] = sanitize_text_field($_GET[EMAIL]);

      if ('' != $email) {
        $params = array('email' => $email, 'role' => 'all');

        // Get from email
        try {
          $results = $woocommerce->get(CUSTOMERS, $params);
          if (null == $results) {
            // Didn't work? Try first and last
            $results = throwParseFirstLastArgsGetCustomers($woocommerce);
          }
        }
        catch(HttpClientException $e) {

          // case: previous try using email exploded. Try first and last.
          $results = throwParseFirstLastArgsGetCustomers($woocommerce);
        }
      }
      else {
          // email wasn't set, so get from first and last:
          $results = throwParseFirstLastArgsGetCustomers($woocommerce);
      }

      // Get the WooCommerce customer ID from the retured customer information.
      global $wc_customer_id;
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

      $wc_customer_id = $_SESSION[WC_CUSTOMER_ID];
      // end: get info from WooCommerce

      unset_get_vars();

      return $musher;
    }
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

    global $wc_customer_id;
    
    try { 
      $stmt = $db->query($execSql, [$wc_customer_id]);
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