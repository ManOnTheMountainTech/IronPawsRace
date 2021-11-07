<?php
    namespace IronPaws;

use WP_Query;

defined( 'ABSPATH' ) || exit;
    define("FORM_NAME", "RSE_Form");

    require_once 'autoloader.php';
    require_once 'wp-defs.php';
    require_once 'debug.php';
    require_once 'util.php';
    require_once 'strings.php';
    require_once 'add-a-team.php';

    class Race_Stage_Entry { 
        protected \WP_User $cur_user;

        const BIB_NUMBER_ID = 'bib_number_id';

        // __construct()
        public function __construct() {
            $this->cur_user = wp_get_current_user();
        }

        static function do_shortcode() {
            $logon_form = ensure_loggedon();
            if (!is_null($logon_form)) {
                return $logon_form;
            }

            return (new Race_Stage_Entry())->makeHTMLRaceStageEntry();
        }

        const HOURS = 'hours';
        const MILEAGE = 'mileage';
        const MINUTES = 'minutes';
        const SECONDS = 'seconds';
        const OUTCOME = 'outcome';

        const RACE_STAGE_RESULTS_PARAMS = 'race_stage_results_params';

        const RACE_STAGE_ENTRY = 'race_stage_entry';
        const RACE_STAGE_ARG = 'race_stage_arg';

        const RAN_CLASS = 'ran_class';

        const OUTCOMES = [
            'completed',
            'dropped',
            'incomplete',
            'untimed',
            'completed_too_late',
            'disqualified',
        ];

        const OUTCOME_DISQUALIFIED_IDX = 5;

        function makeHTMLOptionStringForOutcome(int $i) {
            $outcome = Race_Stage_Entry::OUTCOMES[$i];
            $name = ucfirst($outcome);

            return makeHTMLOptionString($outcome, $name);
        }

        // TODO: Disqualify based on a CRON job, or anything incomplete
        // or not set state as disqualified.
        // Creates the race outompe, such as incomplete, dropped, etc.
        // @return: string -> an HTML string of the race outcome.
        function makeHTMLSelectableOutcomes() {
            $outcomes_html = <<<OUTCOMES_PRE
                <label for="outcome">Outcome:</label>
                <select id="outcome" name="outcome">
            OUTCOMES_PRE;

            for ($i = 0; $i < 4; ++$i) {
                $outcomes_html .= $this->makeHTMLOptionStringForOutcome($i);
            }
                            
            $outcomes_html .= <<<OUTCOMES_POST
                </select>
            OUTCOMES_POST;

            return $outcomes_html;
        }

        function makeProductSelectionForm() {
            $wc_pair_args = WC_PAIR_ARGS;
    
            $wc_rest_api = new WC_Rest();
            $orders = $wc_rest_api->getOrdersByCustomerId($this->cur_user->ID);

            if (empty($orders)) {
                return "No orders found. Have you purchased a race?";
            }
        
            $form_html = <<<RACE_PRE
                <form method="get" action="">
            RACE_PRE;
    
            $race_select = RACE_SELECT;
    
            // Get the products from the orders, then let the customer choose which
            // product (race) they want to go with.
            $form_html .= <<<GET_RACES
                <label for="{$race_select}">Please choose a race to enter results for:</label>
                <select name="{$wc_pair_args}" id="{$race_select}">
            GET_RACES;

            $query_arg_seperator = QUERY_ARG_SEPERATOR;
    
            foreach($orders as $order) {
                if ($wc_rest_api->checkRaceEditable_noThrow($order)) {
                    foreach ($order->line_items as $line_item) {
                    $form_html .= makeHTMLOptionString(
                        "{$line_item->product_id}{$query_arg_seperator}{$order->id}", 
                        $line_item->name);
                    }
                }
            }

            $product_id_const = PRODUCT_ID;
    
            $form_html .= <<<GET_RACES_END
                    </select><br>\n
                    <button type="submit" value="{$wc_pair_args}">Select</button>\n
                </form>\n
            GET_RACES_END;
        
            return $form_html;
        } // end: makeProductSelectionForm


        // IN: GET -> <product id> <QUERY_ARG_SEPERATOR> <order id>
        function makeHTMLRaceStageEntryForm() {
            $wcProductId = 0;
            $wcOrderId = 0;

            $mush_db = null;

            try {
                if (array_key_exists(WC_PAIR_ARGS, $_GET)) {
                    $wc_pair_args = sanitize_text_field($_GET[WC_PAIR_ARGS]);
                    $wc_pairs = explode(QUERY_ARG_SEPERATOR, $wc_pair_args);
                    $wc_product_id_handle_with_care = $wc_pairs[0];
                    $wcProductId = test_number($wc_product_id_handle_with_care);
                    $wc_order_id_handle_with_care = $wc_pairs[1];
                    $wcOrderId = test_number($wc_order_id_handle_with_care);

                    if ($wcProductId < 1) {
                        return __("Invalid product ID supplied");
                    }

                    if ($wcOrderId < 1) {
                        return __("Invalid order ID supplied");
                    }
                } else {
                    return WC_PAIR_ARGS . __(" must be provided.");
                }
            } catch (\Exception $e) {
                return WC_PAIR_ARGS . " is invalid.";
            }

            try {
                $mush_db = new Mush_DB();
            } catch (\PDOException $e) {
                return Strings::$CONTACT_SUPPORT . Strings::$ERROR . 'race-stage-entry_connect-1.';
            }

            // Don't penalize the racer for our slowness in processing.

            $trse_params = null;
            $wc_rest_api = null;

            try {
                $wc_rest_api = new WC_Rest();
                $line_item = $wc_rest_api->get_product_by_id($wcProductId);
            } catch(User_Visible_Exception_Thrower $e) {
                return User_Visible_Exception_Thrower::getUserMessage($e);
            } catch (\Exception $e) {
                return Strings::$CONTACT_SUPPORT . Strings::$ERROR . ' race-stage-entry-5';
            }

            // TODO: 
            // Select the TRSEs that are currentelly active for the date.
            // Fully populate TRSEs in TRSE.php
            $race_controller = new Race_Controller($mush_db, $line_item);
                
            try {
                $trse_params = $mush_db->execAndReturnRow("CALL sp_getTRSEScoreValues(:wc_order_id)", 
                    ['wc_order_id' => $wcOrderId],
                    "Internal error race-stage-entry-1. Please contact support or file a bug.",
                    7);
            } catch (User_Visible_Exception_Thrower $e) {
                if (Mush_DB::EXEC_EXCEPTION_EMPTY == $e->getCode()) {
                    $next_steps = Strings::$NEXT_STEPS;

                    $team_registration_url = home_url('/team-registration/');
                    $register_new_team_url = home_url('/register-a-new-team/');

                    return <<<SELECT_A_TEAM
                        <p>No team selected.</p>
                        <p>$next_steps</p>
                        <a href="{$team_registration_url}">Enter a team in a race.</a><br>
                        <a href="{$register_new_team_url}">Create a new team.</a>
                    SELECT_A_TEAM;
                } else {
                    throw $e;
                }
            }

            $class_ran_in = __("Class ran in:");
            $ran_classes = Teams::makeRunRaceClassesHTML($trse_params[4]);
            $ran_class = self::RAN_CLASS;

            $trse_selections_html = <<<FORM_HEADER
                <form method="post" id="RSE_Form" action="">\n
                <div class="hide-overflow def-pad">\n
                    <label for="ran_class">{$class_ran_in}</label>\n
                    <select id="{$ran_class}" name="{$ran_class}"><br>
                        {$ran_classes}
                    </select>
                </div>\n
            FORM_HEADER;

            $race_stage = $race_controller->calcCurRaceStage();
            if ($race_stage > $race_controller->cur_rd_core_info[TRSE::RD_CORE_STAGES]) {
                return __("This race is over.");
            } else if ($race_stage < 1) {
                return __("This race has not started.");
            }

            // Timed race?
            if (TRSE::TIMED == $race_controller->cur_rd_core_info[TRSE::RD_CORE_RACE_TYPE]) {
                // Make sure that the race is still running
                $hours = __("Hours:");
                $minutes = __("Minutes:");
                $seconds = __("Seconds:");
                $bib_number_user_placeholder = __('1 to 99');
                $bib_number_user  = __('Bib number');

                // Known heredoc bug
                $bib_number_id = self::BIB_NUMBER_ID;
                $wc_product_id_arg = WC_PRODUCT_ID;

                $trse_selections_html .= <<<FORM_BODY
                    <div class="border">\n
                        <div class="hide-overflow disp-flex">\n
                            <div class="def-pad">\n
                                <label for="hours">{$hours}</label>\n
                                <input required min="0" type="number" id="hours" name="hours" class="disp-block">\n
                            </div>\n
                            <div class="def-pad">\n
                                <label for="minutes">{$minutes}</label>\n
                                <input required type="number" min="0" max="60" id="minutes" name="minutes" class="disp-block">\n
                            </div>\n
                            <div class="def-pad">\n
                                <label for="seconds">{$seconds}</label>\n
                                <input required type="number" min="0" max="60" id="seconds" name="seconds" step="0.1" class="disp-block">\n
                            </div>\n
                        </div>\n
                        <div class="hide-overflow-disp-flex">\n
                            <div class="def-pad">\n
                                <label for="{$bib_number_id}">{$bib_number_user}</label>\n
                                <input required type="number" min="1" max="99" 
                                    id="{$bib_number_id}" name="{$bib_number_id}" 
                                    placeholder="{$bib_number_user_placeholder}" class="disp-block">\n
                            </div>\n
                        </div>\n
                    </div>\n
                    <input type="hidden" id="{$wc_product_id_arg}" 
                    name="{$wc_product_id_arg}" value="{$wcProductId}">\n
                FORM_BODY;
            } else {
                $mileage_visible = __("Mileage:");

                $wc_order_id_arg = WC_ORDER_ID;

                $trse_selections_html .= <<<FORM_BODY
                    Race Stage: <strong>{$race_stage}</strong><br><br>\n
                    <div class="border">\n
                        <div class="hide-overflow disp-flex">\n
                            <div class="hide-overflow def-pad">\n
                                <label for="mileage">{$mileage_visible}</label>\n
                                <input required type="number" id="mileage" name="mileage" min="0" step="0.1">\n
                            </div>\n
                        </div>\n
                    </div>\n
                    <input type="hidden" id="{$wc_order_id_arg}" 
                    name="{$wc_order_id_arg}" value="{$wcOrderId}">\n
                FORM_BODY;
            }

            $race_stage_arg = Race_Stage_Entry::RACE_STAGE_ARG;

            $trse_selections_html .= <<<HIDDEN_PART
                <input type="hidden" id="{$race_stage_arg}" 
                name="{$race_stage_arg}" value="{$race_stage}">\n

            HIDDEN_PART;

            $trse_selections_html .= $this->makeHTMLSelectableOutcomes();

            //$trse_selections_html .= "</div>\n"; // for border

            $record_to_server = "Record to server.";

            $trse_selections_html .= <<<FORM_END_GAME
                <br>\n
                <button type="submit">{$record_to_server}</button>\n
            </form>\n
            FORM_END_GAME;

            return $trse_selections_html;
        }

        // params: $_POST
        //  -> Hours, Minutes, Seconds, Race stage, bib_number, product id
        //  or
        //  -> mileage, order id
        //  and
        //  -> Race stage, ran class, and outcome - Enum as string
        function writeToMush_DB() {
            try {
                $hours = -1;
                $minutes = -1;
                $seconds = -1.0;
                $mileage_time = -1;
                $bib_number = 0;
                $wc_order_id = 0;
                $wc_product_id = 0;
                $outcome = null;

                if (array_key_exists(Race_Stage_Entry::HOURS, $_POST)) {
                    $hours = (int)test_number($_POST[Race_Stage_Entry::HOURS]);  

                    if ($hours < 0) {
                        return __("Hours must be positive, you cannot go back in time yet.");
                    }

                    if (array_key_exists(Race_Stage_Entry::MINUTES, $_POST)) {
                        $minutes = (int)test_number($_POST[Race_Stage_Entry::MINUTES]);
                        if ($minutes < 0) {
                            return __("Minutes must be positive. No going back in time permitted.");
                        }

                        if (array_key_exists(Race_Stage_Entry::SECONDS, $_POST)) {
                            $seconds = (int)test_number($_POST[Race_Stage_Entry::SECONDS]);
                            if ($seconds < 0) {
                                return __("Seconds must be positive. Don't be so negative.");
                            }
                            
                            $seconds = round($seconds, 1, PHP_ROUND_HALF_DOWN);

                            $mileage_time = hoursMinutesSecondsToSecondsF($hours,$minutes,$seconds);

                            if (array_key_exists(Race_Stage_Entry::BIB_NUMBER_ID, $_POST)) {
                                $bib_number = (int)test_number($_POST[Race_Stage_Entry::BIB_NUMBER_ID]);
                                if ($bib_number <= 0) {
                                    return __("Bib number must be positive and nonzero.");
                                }

                                if (array_key_exists(WC_PRODUCT_ID, $_POST)) {
                                    $wc_product_id = (int)test_number($_POST[WC_PRODUCT_ID]);
                                    if ($wc_product_id <= 0) {
                                        return __("Product id must be positive and nonzero.");
                                    }
                                }
                            }                            
                        }
                    }
                }

                if (array_key_exists(Race_Stage_Entry::MILEAGE, $_POST)) {
                    $mileage = test_number($_POST[Race_Stage_Entry::MILEAGE]);
                    if (($mileage >= 0) && ($wc_product_id > 0)) {
                        return __('mileage or time can be set, but not both.');
                    }

                    $mileage_time = $mileage;

                    if (array_key_exists(WC_ORDER_ID, $_POST)) {
                        $wc_order_id = test_number($_POST[WC_ORDER_ID]);
                        if ($wc_order_id <= 0) {
                            return __("WooCommerce Order Id must be > 0");
                        }
                    } else {
                        return __("The WooCommerce order id must be specified.");
                    }
                }

                if (($wc_product_id <= 0) && ($wc_order_id <= 0)) {
                    return __("Not all parameters were provided.");
                }

                if (array_key_exists(Race_Stage_Entry::RACE_STAGE_ARG, $_POST)) {
                    $race_stage = test_number($_POST[Race_Stage_Entry::RACE_STAGE_ARG]);
                    if ($race_stage < 0) {
                        return __("Race stage must be greater than 0.");
                    }
                } else {
                    return __("The race stage must be provided.");
                }

                if (array_key_exists(Race_Stage_Entry::OUTCOME, $_POST)) {
                    $outcome = sanitize_text_field($_POST[Race_Stage_Entry::OUTCOME]);
                    if (!in_array($outcome, Race_Stage_Entry::OUTCOMES)) {
                        return __("Invalid race outcome supplied.");
                    }

                    if ($race_stage < 0) {
                        return __("The race stage was not set properly.");
                    }
                } else {
                    return __("The race outcome must be provided.");
                }
              
                if (array_key_exists(Race_Stage_Entry::RAN_CLASS, $_POST)) {
                    $run_class_id = test_number($_POST[Race_Stage_Entry::RAN_CLASS]);
                    if ($run_class_id < 0) {
                        return __("Run class id must be greater than 0");
                    }
                    if ($run_class_id > Teams::MAX_RUN_RACE_CLASSES) {
                        return __("No such run class id.");
                    }
                } else {
                    return __("The class ran in must be provided.");
                }

            } catch(\Exception $e) {
                return $this->defs->GENERIC_INVALID_PARAMETER_MSG;
            }

            try {
                $db = new Mush_DB();
            } catch(\PDOException $e) {
                return __(Strings::$CONTACT_SUPPORT . Strings::$ERROR) . 'reg-a-dog_connect.';
            }

            if (is_wp_debug()) {
                // TODO: Kilometrage?
                "wcOrderId={$wc_order_id}, mileage/time={$mileage_time}, outcome={$outcome}<br>";
                "mileage_time={$mileage_time}, raceStage={$race_stage}<br>";
                "runClassId={$run_class_id}<br>";
            }

            $user_error_msg = __("A failure occured writing this team race stage entry.");

            try {
                if ($bib_number > 0) {
                    $stmt = $db->execSql(
                        "call sp_updateTRSEByBibNumber(
                            :wcProdId,
                            :bibNumber,
                            :milesTimestamp,
                            :outcome,
                            :raceStage,
                            :runClass)",
                        ['wcProdId' => $wc_product_id,
                         'bibNumber' => $bib_number,
                         'milesTimestamp' => $mileage_time,
                         'outcome' => $outcome,
                         'raceStage' => $race_stage,
                         'runClass' => $run_class_id],
                        $user_error_msg);
                } else {
                    $stmt = $db->execSql(
                        "call sp_updateTRSEForRSE(
                            :wcOrderId,
                            :mileage_or_time, 
                            :outcome,  
                            :raceStage, 
                            :runClassId)",
                        ['wcOrderId' => $wc_order_id, 
                        'mileage_or_time' => $mileage_time, 
                        'outcome' => $outcome, 
                        'raceStage' => $race_stage, 
                        'runClassId' => $run_class_id],
                        $user_error_msg);
                }

                unset($_GET);
                unset($_POST);

                $user_return_msg = is_null($stmt) ? "Failed to write" : "Successfully wrote";
                return "{$user_return_msg} race stage <strong>{$race_stage}</strong> to the server.";
            } catch (\Exception $e) {
                return User_Visible_Exception_Thrower::throwErrorCoreException(
                    __("Unable to write the race stage information."), 0, $e);
            }
        }

        // TODO: See if the race was set up to be untimed.
        // Get stage from race information
        function makeHTMLRaceStageEntry() {   
            if (array_key_exists(WC_PAIR_ARGS, $_GET)) {

                if (
                    (array_key_exists(Race_Stage_Entry::HOURS, $_POST) &&
                    array_key_exists(Race_Stage_Entry::MINUTES, $_POST) &&
                    array_key_exists(Race_Stage_Entry::SECONDS, $_POST)) ||
                    array_key_exists(Race_Stage_Entry::MILEAGE, $_POST)) {
                        return $this->writeToMush_DB();
                    } else {
                        return $this->makeHTMLRaceStageEntryForm();
                    }
            }
            
            return $this->makeProductSelectionForm();
        }
    }
?>