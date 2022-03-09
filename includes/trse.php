<?php
  namespace IronPawsLLC;

  defined( 'ABSPATH' ) || exit;

  require_once plugin_dir_path(__FILE__) . 'wp-defs.php';
  require_once plugin_dir_path(__FILE__) . 'debug.php';
  require_once plugin_dir_path(__FILE__) . 'mush-db.php';
  require_once plugin_dir_path(__FiLE__) . 'util.php';
  require_once plugin_dir_path(__FILE__) . 'strings.php';

  use SplDoublyLinkedList;

// Team Race Stage Entry
  class TRSE extends Teams {
    const PRODUCT_ID_IDX = 0;
    const ORDER_ID_IDX = 1;
    const TEAM_IDX = 2;

    const TRSE_BIB_NUMBER_IDX = 0;
    const TRSE_MILES_TIMESTAMP_IDX = 1;
    const TRSE_OUTCOME_IDX = 2;
    const TRSE_WC_CUSTOMER_ID = 3;
    const TRSE_CLASS_ID_IDX = 4;
    const TRSE_NAME_TN_IDX = 5;
    const TRSE_RUN_CLASS_IDX = 6;
    const TRSE_PEOPLE_DISTANCE_UNIT = 7;

    const NONCE_NAME = 'trse_nonce';
    const NONCE_ACTION = 'trse_nonce_action';

    const COMPLETED = 'completed';
    const UNTIMED = 'untimed';

    static function do_shortcode() {
      Strings::init();

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

        $ret = $trse->showProductSelectionForm();

        if (Html_Status::STATUS_DONE == $ret->status) {
          return $ret->html;
        }

        $html = $trse->makeDogTeamSelectionForm(TEAM_REGISTRATION);
      } catch(Race_Registration_Exception $e) {
        return "<strong>{$e->getMessage()}</strong>";
      }

      return $html;
    }

    // @param-> Where to go on submit
    // @return-> html of the dog team selection form
    // @throws->Race_Registration_Exception
    function makeDogTeamSelectionForm(string $where_next) {
      $html = $this->get($where_next);
      if (is_null($html)) {
        return "Unable to get any teams for this musher.";
      }

      $html .= $this->makeFormCloseHTML();

      return $html;
    }

    // IN POST:
    //  @param: self::NONCE_NAME
    //  @param: RACE_PARAMS
    function createFromFinalParams() {

      if ("POST" != $_SERVER['REQUEST_METHOD']) {
        return;
      }

      if (!array_key_exists(self::NONCE_NAME, $_POST)) {
        return __("Nonce not supplied trse.");
      }

      if (!wp_verify_nonce($_POST[self::NONCE_NAME], self::NONCE_ACTION)) {
        return __("Security check failed trse.");
      }

      if (array_key_exists(RACE_PARAMS, $_POST)) {
        $params_handle_with_care = $_POST[RACE_PARAMS];
        $wc_product_id = 0;
        $wc_order_id = 0;
        $team_id = 0;

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
          $error_message = '';

          try {
            $db = new Mush_DB();
          } catch(\PDOException $e) {
              return Strings::$CONTACT_SUPPORT . Strings::$ERROR . 'trse-connect.';
          }

          if (is_wp_debug()) {
            write_log("wc_product_id=$wc_product_id\n");
          }

          // Prevent cardinality violations by enforcing 1 entry per order
          if (is_null($db->execAndReturnIntOrNull("call sp_isOrderInTRSE(?)", 
          [$wc_order_id], 
          1, ("Unable to determine team status.")))) {

            // TODO
            $num_race_stages = $db->execAndReturnIntOrNull(
              "CALL sp_getRaceStagesFromWC(?)", [$wc_product_id],
              "Failure in getting the number of race stages, or the race instance is not set up.");
    
            if (is_wp_debug()) {
              echo "wc_order_id =$wc_order_id | team_id=$team_id | num_race_stages=$num_race_stages<br>";}

            // Preallocate the race stage entries. Better to fail while signing
            // up than during the race.
            if (is_null($num_race_stages)) {
              User_Visible_Exception_Thrower::throwErrorCoreException(__("Can't determine number of race stages."), 1, $e);
            }

            for ($race_stage = 1; $race_stage <= $num_race_stages; ++$race_stage) {
              $trse_id = $db->execAndReturnInt("CALL sp_initTRSE(:wc_order_id, :wcProdId, :team_fk, :race_stage)", 
                ['wc_order_id' => $wc_order_id, 
                'wcProdId' => $wc_product_id,
                'team_fk' => $team_id,
                'race_stage' => $race_stage],
                "Writing the team race stage entry failed for stage {$race_stage}. Please try again. If this continues, contact support or file a bug.");
            }

            if (0 == $trse_id) {
              // Translator: $race_stage - the stage of the race, basically 1 day in a multi-day race.
              $error = __("Team race stage entry {$race_stage} could not be recorded.");

              if (isset($error_message)) {
                $error_message .= $error;
              }
              else {
                $error_message = $error;
              }
            }
          }
        }
        catch(\Exception $e) { 
          var_debug($e);
          return __("Unable to set up the team race stage entry. Is everything set up properly?");
        }
  
        unset($_GET);
        unset($_POST);
        $retHTML = __("Team successfully registered.") . '<br>';
        //$retHTML .= '<button type="button">' . __('Continue') . '</button>';
        return $retHTML;
      }

      return null;
    }

    // Decondes team name and team Id from an untrusted variable,
    // such as $_GET
    // @param: IN OUT->team Id
    // @param: IN OUT->team Name
    // @return: null -> success, otherwise an error message
    static function decodeUnsafeTeamNameId(int &$teamId) {
      try {
        if (array_key_exists(TEAM_NAME_ID, $_GET)) {
          $team_arg_danger_will_robertson = $_GET[TEAM_NAME_ID];
          $team_arg = \sanitize_text_field($team_arg_danger_will_robertson);

          $teamId = test_number($team_arg);
          if ($teamId < 1) {
            return "Bad arg.";
          }
        } else {
          return __("Bad ") . TEAM_NAME_ID;
        }
      } catch (\Exception $e) {
        if (is_wp_debug()) {
          var_dump($e);
        }
        return __("Bad ") . TEAM_NAME_ID . __(" parameters passed in.");
      }

      return null;
    }

    // Previous state: makeOpeningHTML
    // If possible, ultimately produces a string that contains the product 
    // selection form and all tags closed. 
    // Next, Any teams that are assigned to a product are then shown.
    // IN GET:
    //  @param: TEAM_NAME_ID
    // @return: $->status -> STATUS_TRY_NEXT -> Drop through to next step
    //                    -> STATUS_DONE-> Do not drop through, return
    //          $->html   -> the html to show, if any.
    function showProductSelectionForm(): HTML_Status {
      $ret = new HTML_Status();

      if (!getenv('REQUEST_METHOD')) {
        $ret->status = HTML_Status::STATUS_TRY_NEXT;
        return $ret;
      }

      if (array_key_exists(TEAM_NAME_ID, $_GET)) {
        $team_id = 0;
        $team_name_id = 0;

        $error = self::decodeUnsafeTeamNameId($team_id);
        if (!is_null($error)) {
          $ret->html = $error;
          return $ret;
        }

        $team_name_id_arg = TEAM_NAME_ID;
        $race_class_id_arg = RACE_CLASS_ID;
        $wc_product_id = WC_PRODUCT_ID;
  
        $num_unbound_orders = 0;
        $previously_bound_orders_count = 0;
        $cur_user = \wp_get_current_user();
  
        $wc_rest_api = new WC_Rest();
        $orders = $wc_rest_api->getOrdersByCustomerId($cur_user->ID);

        if (is_null($orders) || (empty($orders))) {
          // TODO: Guide users to a race
          $ret->html = "No orders found. Have you purchased a race?";
          return $ret;
        }

        $previously_bound_orders = new SplDoublyLinkedList();
        $previously_bound_orders->setIteratorMode(SplDoublyLinkedList::IT_MODE_DELETE);

        $ret->html = '';
        $select_html = '';
  
        try {
          $db = new Mush_DB();

        // Process the orders into unassigned and assigned (bound) orders
          foreach($orders as $order) {
            if ($wc_rest_api->checkRaceEditable_noThrow($order)) {
              foreach ($order->line_items as $line_item) {
                $rsd = $db->execAndFetchAll("call sp_doesOrderHaveATeam(:wc_orderId)", 
                  ['wc_orderId' => $order->id], "");

                // No team for this order?
                if (is_null($rsd)) {
                  $select_html .= Html_Help::makeHTMLOptionString(
                    $line_item->product_id . QUERY_ARG_SEPERATOR . 
                    $order->id . QUERY_ARG_SEPERATOR .  
                    $team_id, 
                    $line_item->name);

                    // We could just check for null HTML, but it's in flux.
                  ++$num_unbound_orders;
                } else {
                  $previously_bound_orders->push($line_item->name);
                  ++$previously_bound_orders_count;
                }
              } // end: foreach(line_items...)
            }
          } // end: foreach($orders...)
        }
        catch(\Exception $e) {
          return User_Visible_Exception_Thrower::throwErrorCoreException(
            'Error determining if orders have a team.', 0, $e);
        }
  
        $ret->status = HTML_Status::STATUS_DONE;

        // If we don't have any orders to show, then don't show any
        if (0 == $num_unbound_orders) {
          // TODO: Show the status of the users races, if they ask
          if (0 == $previously_bound_orders_count) {
            $ret->html = "<em>No races can be entered into.";
            return $ret;
          }
          // Otherwise, show the orders, since we have at least one.
        } else {
          $method = POST;

          $nonce = wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME, true, false);
    
          $ret->html = <<<RACE_PRE
            <form method="{$method}" action="">
            $nonce
          RACE_PRE;
    
          $race_select = RACE_SELECT;
          $race_params = RACE_PARAMS;
    
          // Get the products from the orders, then let the customer choose which
          // product (race) they want to go with.
          $message = "Please choose a race for your team. Only races that do not have a team are shown.<br>";

          $ret->html .= <<<GET_RACES
              <label for="{$race_select}">{$message}</label>
              <select name="{$race_params}" id="{$race_select}">
          GET_RACES;
          $ret->html .= $select_html;
          $ret->html .= "</select><br>\n";
          $ret->html .= '<button type="submit" value="' . WC_PRODUCT_ID . '">Select</button>';
          $ret->html .= "</form>\n";
        }

        // If we have any orders assigned to teams, show them.
        if ($previously_bound_orders_count > 0) {
          $ret->html .= '<h4>You have races in the following teams:</h4>';

          for ($previously_bound_orders->rewind(); $previously_bound_orders->valid(); $previously_bound_orders->next()) {
            $ret->html .= $previously_bound_orders->current() . '<br>';
          }
        }
          
        $ret->html .= Strings::$NEXT_STEPS . "<br>";
      }

      return $ret;
    } // end: showProductSelectionForm

    // Teams::get
    // End result is to have the team args from the form in _GET
    // @return: GET: TEAM_NAME_ID -> The ids of the team and team name upon
    // form completion.
    // @see: decodeUnsafeTeamNameId() -> Decodes TEAM_NAME_ID
    //  function get(string $form_action) {}
    function makeOpeningHTML(?array $params = null) {
      $team_name_id = TEAM_NAME_ID;
      return <<<GET_TEAMS
            <label for="{$team_name_id}">Please select a dog team:</label>
            <select name="{$team_name_id}" id="{$team_name_id}">
      GET_TEAMS;
    }

    function makeListItemHTML(?array $team_idxs = null) {
      return '<option value="' . $team_idxs[TEAMS::TEAM_IDX] . 
        '">' . $team_idxs[TEAMS::TEAM_NAME_ID] . '</option>';
    }

    function makeClosingHTML(?array $params = null) {
      return "</select><br>";
    }
  }
?>