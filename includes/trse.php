<?php
  namespace IronPaws;

  defined( 'ABSPATH' ) || exit;

  require_once plugin_dir_path(__FILE__) . 'wp-defs.php';
  require_once plugin_dir_path(__FILE__) . 'debug.php';
  require_once plugin_dir_path(__FILE__) . 'mush-db.php';
  require_once plugin_dir_path(__FiLE__) . 'util.php';
  require_once plugin_dir_path(__FILE__) . 'strings.php';

  use Automattic\WooCommerce\Client;
  use Automattic\WooCommerce\HttpClient\HttpClientException;

  // Team Race Stage Entry
  class TRSE extends Teams {
    const PRODUCT_ID_IDX = 0;
    const ORDER_ID_IDX = 1;
    const TEAM_IDX = 2;

    const TRSE_MILES_IDX = 1;
    const TRSE_OUTCOME_IDX = 2;
    const TRSE_RACE_CLASSES_IDX = 4;
    const RUN_RACE_CLASS_ID_IDX = 7;

    const RI_START_DATE_TIME = 3;
    const RI_RACE_DEFS_FK = 2;

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
        $params_handle_with_care = $_POST[RACE_PARAMS];
        $wc_product_id;
        $wc_order_id;
        $team_id;

        try {
          $params = sanitize_text_field($params_handle_with_care);
          $params = explode(QUERY_ARG_SEPERATOR, $params);

          $wc_product_id = test_number($params[self::PRODUCT_ID_IDX]);
          if (0 == $wc_product_id) {
            return "Invalid product id argument";
          }

          $team_id = test_number($params[self::TEAM_IDX]);
          if (0 == $team_id) {
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
          $error_message;

          try {
            $db = new Mush_DB();
          } catch(\PDOException $e) {
              return Strings::CONTACT_SUPPORT . Strings::ERROR . 'trse-connect.';
          }

          if (is_wp_debug()) {
            write_log("wc_product_id=$wc_product_id\n");
          }

          $num_race_stages = $db->execAndReturnInt(
            "CALL sp_getRaceStagesFromWC(?)", [$wc_product_id],
            "Failure in getting the number of race stages.");
  
          if (is_wp_debug()) {
            echo "wc_order_id =$wc_order_id | team_id=$team_id | num_race_stages=$num_race_stages | wc_product_id=$wc_product_id\n";}

          // Preallocate the race stage entries. Better to fail while signing
          // up than during the race.
          for ($race_stage = 1; $race_stage <= $num_race_stages; ++$race_stage) {
            $trse_id = $db->execAndReturnInt("CALL sp_initTRSE(:wc_order_id, :wc_product_id, :team_fk, :race_stage)", 
              ['wc_order_id' => $wc_order_id, 
              'wc_product_id' => $wc_product_id,
              'team_fk' => $team_id,
              'race_stage' => $race_stage],
              "Writing the team race stage entry failed for stage {$race_stage}. Please try again. If this continues, contact support or file a bug.");
          }

          if (0 == $trse_id) {
            if (isset($error_message)) {
              $error_message = "Team race stage entry {$race_stage} could not be recorded.";
            }
            else {
              $error_message .= "Team race stage entry {$race_stage} could not be recorded.";
            }
          }
        }
        catch(Mush_DB_Exception $e) { 
          statement_log(__FUNCTION__ , __LINE__ , ': produced exception' . var_debug($e));
          return $e->userHTMLMessage;
        }

        unset($_GET);
        unset($_POST);
        return "Team successfully registered.";
      }

      return null;
    }

    // Previous state: makeOpeningHTML
    function showProductSelectionForm() {
      if (array_key_exists(TEAM_ARGS, $_GET)) {
        $team_id;
        $team_name_id;

        try {
          $team_args_danger_will_robertson = $_GET[TEAM_ARGS];
          $team_args = sanitize_text_field($team_args_danger_will_robertson);

          $team_params_unsafe = explode(QUERY_ARG_SEPERATOR, $team_args);
          $team_params_size = count($team_params_unsafe);
          if (2 != $team_params_size) {
            return "Invalid number of team params passed in";
          }

          $team_id = test_number($team_params_unsafe[0]);
          if ($team_id < 1) {
            return "Bad team id passed in.";
          }

          $team_name_id = test_number($team_params_unsafe[1]);
          if ($team_name_id < 1) {
            return "Bad team name passed in.";
          }
        } catch (\Exception $e) {
          if (is_wp_debug()) {
            var_dump($e);
          }
          return "Bad parameters passed in.";
        }

        $team_name_id_arg = TEAM_NAME_ID;
        $race_class_id_arg = RACE_CLASS_ID;
        $wc_product_id = WC_PRODUCT_ID;
  
        $i = 0;
        $cur_user = wp_get_current_user();
  
        $wc_rest_api = new WC_Rest();
        $orders = $wc_rest_api->getOrdersByCustomerId($cur_user->ID);

        if (is_null($orders) || (empty($orders))) {
          return "No orders found. Have you purchased a race?";
        }
  
        $wc_order_id = WC_ORDER_ID;

        $method = POST;
   
        $form_html = <<<RACE_PRE
          <form method="{$method}" action="">
        RACE_PRE;
  
        $race_select = RACE_SELECT;
        $race_params = RACE_PARAMS;
  
        // Get the products from the orders, then let the customer choose which
        // product (race) they want to go with.
        $message = "Please choose a race for your team.";

        $form_html .= <<<GET_RACES
            <label for="{$race_select}">{$message}</label>
            <select name="{$race_params}" id="{$race_select}">
        GET_RACES;
  
        foreach($orders as $order) {
          if ($wc_rest_api->checkRaceEditable_noThrow($order)) {
            foreach ($order->line_items as $line_item) {
              $form_html .= makeHTMLOptionString(
                $line_item->product_id . QUERY_ARG_SEPERATOR . 
                $order->id . QUERY_ARG_SEPERATOR .  
                $team_id, 
                $line_item->name);
            }
          }
  
          ++$i;
        }
  
        $form_html .= "</select><br>\n";
        $form_html .= '<button type="submit" value="' . WC_PRODUCT_ID . '">Select</button>';
        $form_html .= "</form>\n";
  
        if (0 == $i) {
          $form_html = "<em>No races can be entered into..";
        }

        return $form_html;
      }

      return null;
    } // end: showProductSelectionForm

    //function get(string $form_action) {}

    function makeOpeningHTML() {
      $team_args = TEAM_ARGS;
      $team_name_id = TEAM_NAME_ID;
      return <<<GET_TEAMS
            <label for="{$team_name_id}">Please select a dog team:</label>
            <select name="{$team_args}" id="{$team_name_id}">
      GET_TEAMS;
    }

    function makeListItemHTML(array $team_idxs) {
      return '<option value="' . $team_idxs[TEAMS::TEAM_IDX] . QUERY_ARG_SEPERATOR . 
        $team_idxs[TEAMS::TEAM_TN_FK] . '">' . $team_idxs[TEAMS::TEAM_NAME_ID] . '</option>';
    }

    function makeClosingHTML() {
      return "</select><br>";
    }
  }
?>