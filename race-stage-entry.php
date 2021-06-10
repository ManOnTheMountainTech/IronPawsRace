<?php
    defined( 'ABSPATH' ) || exit;

    namespace IronPaws;

    define("FORM_NAME", "RSE_Form");

    require_once plugin_dir_path(__FILE__) . 'autoloader.php';
    require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
    require_once plugin_dir_path(__FILE__) . 'includes/util.php';

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

        const RACE_CLASS_ID = 'race_class_id';
        const RACE_STAGE_RESULTS_PARAMS = 'race_stage_results_params';

        const RACE_STAGE_ENTRY = 'race_stage_entry';
        const RACE_STAGE_ARG = 'race_stage_arg';

        const OUTCOMES = [
            'completed',
            'dropped',
            'incomplete',
            'untimed',
            'completed_too_late',
            'disqualified',
        ];

        const OUTCOME_DISQUALIFIED_IDX = 5;

        const RI_START_DATE_TIME = 3;
        const RI_RACE_DEFS_FK = 2;

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

            $i = 0;
    
            $wc_rest_api = new WC_Rest();
            $orders = $wc_rest_api->getOrdersByCustomerId($this->cur_user->ID);
        
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
    
            foreach($orders as $order) {
                if ($wc_rest_api->checkRaceEditable_noThrow($order)) {
                    foreach ($order->line_items as $line_item) {
                    $form_html .= makeHTMLOptionString(
                        "{$line_item->product_id}|{$order->id}", 
                        $line_item->name);
                    }
                }
    
                ++$i;
            }
    
            $form_html .= "</select><br>\n";
            $form_html .= '<button type="submit" value="' . WC_PAIR_ARGS . '">Select</button>';
            $form_html .= "</form>";
    
            if (0 == $i) {
                $form_html = "<em>No orders have been placed.";
            }
        
            return $form_html;
        } // end: makeProductSelectionForm

        function makeHTMLRaceStageEntryForm() {
            $wpProductId;

            try {
                $wc_pair_args = test_input($_GET[WC_PAIR_ARGS]);
                $pieces = explode('|', $wc_pair_args);
                $wpProductId = $pieces[0];
                if ($wpProductId < 1) {
                    return "Invalid product id supplied.";
                }
            } catch (\Exception $e) {
                return "Invalid parameters specified.";
            }

            $mush_db = new Mush_DB();

            // Don't penalize the racer for our slowness in processing.
            $cur_date_time = date_create();

            try {
                // TODO: 
                // Select the TRSEs that are currentelly active for the date.
                // Fully populate TRSEs in TRSE.php
                // Figure out what stage we are racing
                $person_id = $mush_db->execAndReturnInt(
                    'CALL sp_getPersonIdFromWPUserId (?)',
                    [$this->cur_user->ID],
                    "Unfortunately the user id could not be retrieved.");

                $stmt = $mush_db->execSql('CALL sp_getAllRaceInstanceInfo(?)',
                [$wpProductId],
                "Unable to get information about the race");

                if (is_null($stmt)) {
                    return "Unable to get information about the race.";
                }
    
                $cur_ri_info = $stmt->fetchAll(\PDO::FETCH_NUM);
                $stmt->closeCursor();

                $cur_ri_info = $cur_ri_info[0]; // Should only be 1

                $race_start_date_time = date_create($cur_ri_info[Race_Stage_Entry::RI_START_DATE_TIME]);
                $ri_race_defs_fk = $cur_ri_info[Race_Stage_Entry::RI_RACE_DEFS_FK];

                $num_race_stages = $mush_db->execAndReturnInt(
                    'CALL sp_getNumRaceStagesByRD (?)',
                    [$ri_race_defs_fk],
                    "Unfortunately the number of race stages could not be retrieved.");
            }
            catch(Mush_DB_Exeption $e) {
                statement_log(__FUNCTION__, __LINE__, "Produced exception", $e);
                return $e->userHTMLMessage;
            }

            $elapsed_race_days = ($cur_date_time->diff($race_start_date_time))->days;
            $race_stage = intdiv($elapsed_race_days, 7) + 1;

            if ($race_stage > $num_race_stages) {
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

            $trse_selections_html .= <<<FORM_BODY
                    <div class="border">\n
                        <div>\n
                            <div class="hide-overflow def-pad">\n
                                <label for="mileage">Mileage*:</label>\n
                                <input required type="number" id="mileage" name="mileage">\n
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
                    <div>\n
            FORM_BODY;

            $trse_selections_html .= $this->makeHTMLSelectableOutcomes();

            $trse_selections_html .= "</div>\n"; // for border

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

                $race_stage_instance = test_number($_POST[Race_Stage_Entry::RACE_STAGE_ARG]);
                $wc_pair_args = sanitize_text_field($_GET[WC_PAIR_ARGS]);
                $wc_pairs = explode('|', $wc_pair_args);
                $wc_order_id_handle_with_care = $wc_pairs[1];
                $wc_order_id = test_number($wc_order_id_handle_with_care);

                if ($wc_order_id < 1) {
                    return "Invalid order ID supplied";
                }

                $outcome = sanitize_text_field($_POST[Race_Stage_Entry::OUTCOME]);
                if (!in_array($outcome, Race_Stage_Entry::OUTCOMES)) {
                    return "Invalid race outcome supplied.";
                }

            } catch(\Exception $e) {
                return GENERIC_INVALID_PARAMETER_MSG;
            }

            $db = new Mush_DB();

            $race_elapsed_time = sprintf("%02d:%02d:%05.2F", $hours, $minutes, $seconds);

            $db->execSql("call sp_updateTRSEForRSE(:wcOrderId, :mileage, :outcome, :raceElapsedTime, :raceStageInstance, :dateCreated)",
                ['wcOrderId' => $wc_order_id, 
                'mileage' => $mileage, 
                'outcome' => $outcome, 
                'raceElapsedTime' => $race_elapsed_time, 
                'raceStageInstance' => $race_stage_instance, 
                'dateCreated' => date("Y-m-d H:i:s")]);

            return "Successfully wrote race stage <strong>{$race_stage_instance}</strong> to the server.";
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