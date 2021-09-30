<?php
    namespace IronPaws;

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
            $wcProductId;
            $wcOrderId;

            try {
                $wc_pair_args = test_input($_GET[WC_PAIR_ARGS]);
                $pieces = explode(QUERY_ARG_SEPERATOR, $wc_pair_args);

                $wcProductId = $pieces[0];
                if ($wcProductId < 1) {
                    return "Invalid product id supplied.";
                }
                
                $wcOrderId = $pieces[1];
                if ($wcOrderId < 1) {
                    return "Invalid order id supplied.";
                }
            } catch (\Exception $e) {
                return "Invalid parameters specified.";
            }

            $mush_db;

            try {
                $mush_db = new Mush_DB();
            } catch (\PDOException $e) {
                return Strings::CONTACT_SUPPORT . Strings::ERROR . 'race-stage-entry_connect-1.';
            }

            // Don't penalize the racer for our slowness in processing.

            $trse_params = null;

            try {
                // TODO: 
                // Select the TRSEs that are currentelly active for the date.
                // Fully populate TRSEs in TRSE.php
                $some_info = new Some_Race_Info($mush_db, $wcProductId);
                
                $trse_params = $mush_db->execAndReturnColumn("CALL sp_getTRSEScoreValues(:wc_order_id)", 
                    ['wc_order_id' => $wcOrderId],
                    "Internal error race-stage-entry-1. Please contact support or file a bug.");

                $next_steps = Strings::NEXT_STEPS;
                
                if (empty($trse_params)) {
                    return <<<SELECT_A_TEAM
                        <p>$next_steps</p>
                        <p>No team selected.</p>
                        <a href="/team-registration">Enter a team in a race.</a><br>
                        <a href="/register-a-new-team">Create a new team.</a>
                    SELECT_A_TEAM;
                }

                $trse_params = $trse_params[0];
            }
            catch(\Exception $e) {
                return User_Visible_Exception_Thrower::getUserMessage($e);
            }

            $race_stage = $some_info->calcCurRaceStage();

            if ($race_stage > $some_info->num_race_stages) {
                return "This race is over.";
            } else if ($race_stage < 1) {
                return "This race has not started.";
            }

            $trse_selections_html = <<<FORM_HEADER
                <form method="post" id="RSE_Form" action="">\n
                Race Stage: <strong>{$race_stage}</strong><br><br>\n
            FORM_HEADER;

            $hours = Race_Stage_Entry::HOURS;
            $mileage = Race_Stage_Entry::MILEAGE;
            $minutes = Race_Stage_Entry::MINUTES;

            $ran_classes = Teams::makeRunRaceClassesHTML($trse_params[4]);
            $ran_class = self::RAN_CLASS;

            $trse_selections_html .= <<<FORM_BODY
                <div class="border">\n
                    <div class="hide-overflow disp-flex">\n
                        <div class="hide-overflow def-pad">\n
                            <label for="mileage">Mileage*:</label>\n
                            <input required type="number" id="mileage" name="mileage" min="0" step="0.1">\n
                        </div>\n
                        <div class="hide-overflow def-pad">\n
                            <label for="ran_class">Class ran in*:</label>\n
                            <select id="{$ran_class}" name="{$ran_class}"><br>
                                {$ran_classes}
                            </select>
                        </div>\n
                    </div>\n
                    <div class="hide-overflow disp-flex">\n
                        <div class="def-pad">\n
                            <label for="hours">Hours*:</label>\n
                            <input required min="0" type="number" id="hours" name="hours" class="disp-block">\n
                        </div>\n
                        <div class="def-pad">\n
                            <label for="minutes">Minutes*:</label>\n
                            <input required type="number" min="0" max="60" id="minutes" name="minutes" class="disp-block">\n
                        </div>\n
                        <div class="def-pad">\n
                            <label for="seconds">Seconds*:</label>\n
                            <input required type="number" min="0" max="60" id="seconds" name="seconds" step="0.1" class="disp-block">\n
                        </div>\n
                    </div>\n
                </div>\n
            FORM_BODY;

            $trse_selections_html .= $this->makeHTMLSelectableOutcomes();

            //$trse_selections_html .= "</div>\n"; // for border

            $race_stage_arg = Race_Stage_Entry::RACE_STAGE_ARG;

            $trse_selections_html .= <<<HIDDEN_PART
                <input type="hidden" id="{$race_stage_arg}" 
                name="{$race_stage_arg}" value="{$race_stage}">\n
            HIDDEN_PART;

            $trse_selections_html .= <<<FORM_END_GAME
                <br>\n
                <button type="submit">Record to server</button>\n
            </form>\n
            FORM_END_GAME;

            return $trse_selections_html;
        }

        // params: $_POST
        //  -> Hours, Minutes, Seconds, Mileage, Race stage,
        //      -> WC_PAIR_ARGS ->
        //          product id <QUERY_ARG_SEPERATOR> order id
        //      -> Outcome - Enum as string
        function writeToMush_DB() {
            try {
                $hours = test_number($_POST[Race_Stage_Entry::HOURS]);
                if ($hours < 0) {
                    return "Hours must be positive, you cannot go back in time yet.";
                }

                $minutes = test_number($_POST[Race_Stage_Entry::MINUTES]);
                if ($minutes < 0) {
                    return "Minutes must be positive.";
                }

                $seconds = test_number($_POST[Race_Stage_Entry::SECONDS]);
                if ($seconds < 0) {
                    return "Seconds must be positive. Don't be so negative.";
                }

                $mileage = test_number($_POST[Race_Stage_Entry::MILEAGE]);
                if ($mileage < 0) {
                    return "Mileage must be positive. Are you sure you were going the right direction?";
                }

                $race_stage = test_number($_POST[Race_Stage_Entry::RACE_STAGE_ARG]);
                if ($race_stage < 0) {
                    return "Race stage must be greater than 0";
                }

                $wc_pair_args = sanitize_text_field($_GET[WC_PAIR_ARGS]);
                $wc_pairs = explode(QUERY_ARG_SEPERATOR, $wc_pair_args);
                $wc_product_id_handle_with_care = $wc_pairs[0];
                $wc_product_id = test_number($wc_product_id_handle_with_care);
                $wc_order_id_handle_with_care = $wc_pairs[1];
                $wc_order_id = test_number($wc_order_id_handle_with_care);

                if ($wc_product_id < 1) {
                    return "Invalid product ID supplied";
                }

                if ($wc_order_id < 1) {
                    return "Invalid order ID supplied";
                }

                $outcome = sanitize_text_field($_POST[Race_Stage_Entry::OUTCOME]);
                if (!in_array($outcome, Race_Stage_Entry::OUTCOMES)) {
                    return "Invalid race outcome supplied.";
                }

                $run_class_id = test_number($_POST[Race_Stage_Entry::RAN_CLASS]);
                if ($run_class_id < 0) {
                    return "Run class id must be greater than 0";
                }
                if ($run_class_id > Teams::MAX_RUN_RACE_CLASSES) {
                    return "No such run class id.";
                }

            } catch(\Exception $e) {
                return GENERIC_INVALID_PARAMETER_MSG;
            }

            try {
                $db = new Mush_DB();
            } catch(\PDOException $e) {
                return Strings::CONTACT_SUPPORT . Strings::ERROR . 'reg-a-dog_connect.';
            }

            $race_elapsed_time = sprintf("%02d:%02d:%05.2F", $hours, $minutes, $seconds);
            $date_created = date("Y-m-d H:i:s");

            if (is_wp_debug()) {
                // TODO: Kilometrage?
                "wcOrderId={$wc_order_id}, mileage={$mileage}, outcome={$outcome}<br>";
                "raceElapsedTime={$race_elapsed_time}, wcProductId={$wc_product_id}, raceStage={$race_stage}<br>";
                "dateCreated={$date_created}, runClassId={$run_class_id}<br>";
            }

            try {
                $modified_columns = $db->execSql(
                    "call sp_updateTRSEForRSE(
                        :wcOrderId, 
                        :mileage, 
                        :outcome, 
                        :raceElapsedTime, 
                        :raceStage, 
                        :dateCreated, 
                        :runClassId)",
                    ['wcOrderId' => $wc_order_id, 
                    'mileage' => $mileage, 
                    'outcome' => $outcome, 
                    'raceElapsedTime' => $race_elapsed_time,
                    'raceStage' => $race_stage, 
                    'dateCreated' => $date_created,
                    'runClassId' => $run_class_id],
                    "A failure occured writing this team race stage entry.");

                unset($_GET);
                unset($_POST);

                $user_return_msg = (empty($modified_columns)) ?  "Failed to write" : "Successfully wrote";
            } catch (\Exception $e) {
                return User_Visible_Exception_Thrower::getUserMessage($e);
            }
                 
            return "{$user_return_msg} race stage <strong>{$race_stage}</strong> to the server.";
        }

        // TODO: See if the race was set up to be untimed.
        // Get stage from race information
        function makeHTMLRaceStageEntry() {

            if (array_key_exists(Race_Stage_Entry::HOURS, $_POST) &&
                array_key_exists(Race_Stage_Entry::MINUTES, $_POST) &&
                array_key_exists(Race_Stage_Entry::MILEAGE, $_POST) &&
                array_key_exists(Race_Stage_Entry::SECONDS, $_POST) &&
                array_key_exists(Race_Stage_Entry::RACE_STAGE_ARG, $_POST) &&
                array_key_exists(WC_PAIR_ARGS, $_GET)) {
                    return $this->writeToMush_DB();
                }
            else
                if (array_key_exists(WC_PAIR_ARGS, $_GET)) {
                    return $this->makeHTMLRaceStageEntryForm();
                }
            
            return $this->makeProductSelectionForm();
        }
    }
?>