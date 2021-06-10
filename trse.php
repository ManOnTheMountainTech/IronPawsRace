<?php
  defined( 'ABSPATH' ) || exit;

  namespace IronPaws;

  require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
  require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
  require_once plugin_dir_path(__FILE__) . 'mush-db.php';
  require_once plugin_dir_path(__FiLE__) . 'includes/util.php';
  //require_once plugin_dir_path(__FILE__) . 'orders.php';

  use Automattic\WooCommerce\Client;
  use Automattic\WooCommerce\HttpClient\HttpClientException;

  // Team Race Stage Entry
  class TRSE extends Teams {
    const PRODUCT_ID_IDX = 0;
    const ORDER_ID_IDX = 1;
    const TEAM_NAME__ID_IDX = 2;
    
    static function do_shortcode() {
      $logon_form = ensure_loggedon();
      if (!is_null($logon_form)) {
        return $logon_form;
      }

      $trse = new TRSE();

      try { 
        $html = $trse->createFromFinalParams();
        if (!is_null($html)) {
          return $html;
        }

        $html = $trse->showProductSelectionForm();
        if (!is_null($html)) {
          return $html;
        }

        $html = $trse->get(TEAM_REGISTRATION);
        if (is_null($html)) {
          return "Unable to get any teams for this musher.";
        }

        $html .= $trse->makeFormCloseHTML();
      } catch(Race_Registration_Exception $e) {
        return "<strong>{$e->getMessage()}</strong>";
      }

      return $html;
    }

    function createFromFinalParams() {
      if (array_key_exists(RACE_PARAMS, $_POST)) {
        $params_handle_with_care = $_POST[RACE_PARAMS]);
        $wc_product_id;
        $wc_order_id;
        $team_id;

        try {
          $params = sanitize_text_field($params_handle_with_care);
          $params = explode('|', $params);

          $wc_product_id = test_number($params[self::PRODUCT_ID_IDX]);
          if (0 == $wc_product_id) {
            return "Invalid product id argument";
          }

          $team_name_id = test_number($params[self::TEAM_NAME_ID_IDX]);
          if (0 == $team_name_id) {
            return "Invalid team name id argument";
          }

          $wc_order_id = test_number($params[self::ORDER_ID_IDX]);
          if (0 == $wc_order_id) {
            return "Invalid order id argument";
          }
        } catch(\Exception $e) {
          return "The supplied parameters could not be parsed.";
        }

        try {
          $db = new Mush_DB();
          $people_id = $db->execAndReturnInt(
            "CALL sp_getPersonIdFromWPUserId (?)", [$this->wp_user->ID],
            "Unable to get the person id from wp user id={$this->wp_user->ID}");

          $team_ids = $db->execAndReturnInt(
            "CALL sp_getMushersTeams (?)", [$people_id],
            "Unable to get the person id from wp user id={$this->wp_user->ID}");

          $rsi_id = $db->execAndReturnInt(
            "CALL sp_getRSIByWCProdId (?)", [$wc_product_id],
            "Unable to get the RSI id from wp user id={$this->wp_user->ID}");

          $team_id = $db->execAndReturnInt(
            "CALL sp_getTeamsByTeamNameId (?)", [$team_name_id],
            "Unable to get the team name id from wp user id={$this->wp_user->ID}");

          if (is_wp_debug()) {
            echo "$wc_order_id | $rsi_id | $team_id";}

          $trse_id = $db->execAndReturnInt("CALL sp_initTRSE(:wc_order_id, :race_stage_instance_fk, :team_fk)", 
            ['wc_order_id' => $wc_order_id, 
            'race_stage_instance_fk' => $rsi_id, 
            'team_fk' => $team_id],
            "Writing the team race stage entry failed. Please try again.");
        }
        catch(Mush_DB_Exception $e) { 
            statement_log(__FUNCTION__ , __LINE__ , ': produced exception', $e);
            return $e->userFriendlyMessage;
          }

          return "Team successfully registered.";
        }

        return null;
    }

    function showProductSelectionForm() {
      if (array_key_exists(TEAM_NAME_ID, $_GET)) {
        $team_name_id = test_number($_GET[TEAM_NAME_ID]);
        if ($team_name_id < 1) {
          return "Bad team name id passed in.";
        }

        $team_name_id_arg = TEAM_NAME_ID;
        $race_class_id_arg = RACE_CLASS_ID;
        $wc_product_id = WC_PRODUCT_ID;
  
        $i = 0;
        $cur_user = wp_get_current_user();
  
        $wc_rest_api = new WC_Rest();
        $orders = $wc_rest_api->getOrdersByCustomerId($cur_user->ID);
  
        $wc_order_id = WC_ORDER_ID;
   
        $form_html = <<<RACE_PRE
          <form method="{$method}" action="">
        RACE_PRE;
  
        $race_select = RACE_SELECT;
        $race_params = RACE_PARAMS;
  
        // Get the products from the orders, then let the customer choose which
        // product (race) they want to go with.
        $form_html .= <<<GET_RACES
            <label for="{$race_select}">{$message}</label>
            <select name="{$race_params}" id="{$race_select}">
        GET_RACES;
  
        foreach($orders as $order) {
          if ($wc_rest_api->checkRaceEditable_noThrow($order)) {
            foreach ($order->line_items as $line_item) {
              $form_html .= makeHTMLOptionString(
                $line_item->product_id . '|' . 
                $order->id . '|' .  
                $team_name_id, 
                $line_item->name);
            }
          }
  
          ++$i;
        }
  
        $form_html .= "</select><br>\n";
        $form_html .= '<button type="submit" value="' . WC_PRODUCT_ID . '">Select</button>';
        $form_html .= "</form>";
  
        if (0 == $i) {
          $form_html = "<em>No orders have been placed.";
        }
      }

      return null;
    } // end: showProductSelectionForm

    function makeOpeningHTML() {
      $team_name_id = TEAM_NAME_ID;
      return <<<GET_TEAMS
            <label for="{$team_name_id}">Please select a team to race:</label>
            <select name="{$team_name_id}" id="{$team_name_id}">
      GET_TEAMS;
    }

    function makeListItemHTML(array $row) {
      return '<option value="' . $row[0] . '">' . $row[1] . '</option>';
    }

    function makeClosingHTML() {
      return "</select><br>";
    }
  }
?>