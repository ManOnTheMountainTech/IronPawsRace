<?php
    namespace IronPaws;

    use Throwable;
    use WP_Query;

    defined('ABSPATH') || exit;
    define("FORM_NAME", "RSE_Form");

    require_once 'autoloader.php';
    require_once 'wp-defs.php';
    require_once 'debug.php';
    require_once 'util.php';
    require_once 'strings.php';
    require_once 'add-a-team.php';

    class Race_Stage_Entry { 
        const NONCE_NAME = "RSE-nonce";
        const NONCE_ACTION = "RSE-nonce-action";
        
        protected \WP_User $cur_user;

        static array $TIMED_PATH_QUERY_ARGS;
        static array $NON_RACING_POINTS_ARGS;
        static HTML_Help $timed_html_help;
        static HTML_Help $non_racing_points;

        static $ERROR_MSG;
        
        // __construct()
        public function __construct() {
            $this->cur_user = wp_get_current_user();

            self::$TIMED_PATH_QUERY_ARGS = [
                'hours',
                'minutes', // 1
                'seconds',
                WC_PRODUCT_ID, // 3
                'bib_number'];

            self::$NON_RACING_POINTS_ARGS = [
                'howladays',  // 0
                'volunteering' //  
            ];

            self::$ERROR_MSG = __("Error in race stage");

            self::$timed_html_help = new HTML_Help(self::$TIMED_PATH_QUERY_ARGS, $_POST);
            self::$non_racing_points = new HTML_Help(self::$NON_RACING_POINTS_ARGS, $_POST);

            WP_Defs::init();
        }

        static function throw(int $line_number) {
            User_Visible_Exception_Thrower::throwErrorCoreException(
                self::$ERROR_MSG,
                $line_number);
        }

        static function do_shortcode() {
            $logon_form = ensure_loggedon();
            if (!is_null($logon_form)) {
                return $logon_form;
            }

            Strings::init();

            return (new Race_Stage_Entry())->makeHTMLRaceStageEntry();
        }

        const QUERY_ARGS_COUNT = 7;

        const HOURS_IDX = 0;
        const MINUTES_IDX = 1;
        const SECONDS_IDX = 2;
        const WC_PRODUCT_ID_IDX = 3;
        const BIB_NUMBER_IDX = 4;
        
        const HOWLADAYS_IDX = 0;
        const VOLUNTEERING_IDX = 1;

        const OUTCOME = 'outcome';

        const RACE_STAGE_RESULTS_PARAMS = 'race_stage_results_params';

        const RACE_STAGE_ENTRY = 'race_stage_entry';
        const RACE_STAGE_ARG = 'race_stage_arg';
        
        const DISTANCE_UNIT = 'distance_unit';
        const DISTANCE_TRAVELED = 'distance_traveled';
        const IS_KILOMETERS = 'is_kilometers';

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

            return Html_Help::makeHTMLOptionString($outcome, $name);
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
                </select><br>
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
                    $form_html .= Html_Help::makeHTMLOptionString(
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

            if (array_key_exists("REQUEST_METHOD", $_SERVER) && $_SERVER["REQUEST_METHOD"] !== 'GET') {
                return __("Invalid request state race-results phase 2.");
            }

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
            } catch(Throwable $e) {
                return User_Visible_Exception_Thrower::getUserMessage($e);
            }

            // TODO: 
            // Select the TRSEs that are currentelly active for the date.
            // Fully populate TRSEs in TRSE.php
            $race_controller = new Race_Controller($mush_db, $line_item);
                
            try {
                $trse_params = $mush_db->execAndReturnRow("CALL sp_getTRSEScoreValues(:wc_order_id)", 
                    ['wc_order_id' => $wcOrderId],
                    "Internal error race-stage-entry-1. Please contact support or file a bug.",
                    8);
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
            $ran_classes = Teams::makeRunRaceClassesHTML($trse_params[TRSE::TRSE_CLASS_ID_IDX]);
            $ran_class = self::RAN_CLASS;

            $nonce = wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME, true, false);

            $trse_selections_html = "";

            $ran_class_html = <<<FORM_HEADER
                <form method="post" id="RSE_Form" action="">\n
                $nonce
                <div class="hide-overflow def-pad">\n
                    <label for="ran_class">{$class_ran_in}</label>\n
                    <select id="{$ran_class}" name="{$ran_class}"><br>
                        {$ran_classes}
                    </select>
                </div>\n
            FORM_HEADER;

            $race_stage = $race_controller->calcCurRaceStage();
            $cur_rd_core_info = $race_controller->cur_rd_core_info;

            if ($race_stage > $cur_rd_core_info[Race_Definition::CORE_STAGES]) {
                return __("This race is over.");
            } else if ($race_stage < 1) {
                return __("This race has not started.");
            }

            $outcomes_html = $this->makeHTMLSelectableOutcomes();

            // Timed race?
            if (Race_Definition::TIMED == $cur_rd_core_info[Race_Definition::CORE_RACE_TYPE]) {
                // Make sure that the race is still running
                $hours = __("Hours:");
                $minutes = __("Minutes:");
                $seconds = __("Seconds:");
                $bib_number_user_placeholder = __('1 to 99');
                $bib_number_user  = __('Bib number');

                // Known heredoc bug
                $bib_number = self::$TIMED_PATH_QUERY_ARGS[self::BIB_NUMBER_IDX];
                $wc_product_id_arg = WC_PRODUCT_ID;

                $trse_selections_html .= <<<FORM_BODY
                    Day: <strong>{$race_stage}</strong><br>\n
                    {$ran_class_html}
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
                            </div>
                        </div>
                        <div class="hide-overflow disp-flex">
                            <div class="def-pad">
                                <label for="{$bib_number}">{$bib_number_user}</label>
                                <input required type="number" min="1" max="99" 
                                    id="{$bib_number}" name="{$bib_number}" 
                                    placeholder="{$bib_number_user_placeholder}" class="disp-block">
                            </div>
                        </div>
                        <div class="hide-overflow disp-flex">
                            <div class="def-pad">
                                {$outcomes_html}
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="{$wc_product_id_arg}" 
                        name="{$wc_product_id_arg}" value="{$wcProductId}">
                FORM_BODY;
            } else {
                $distance_unit_value = $trse_params[TRSE::TRSE_PEOPLE_DISTANCE_UNIT];
                $useKilometers = (Units::KILOMETERS == $distance_unit_value);
                $distance_unit_visible = ($useKilometers) ? __("Kilometerage:") : __("Mileage:");

                $wc_order_id_arg = WC_ORDER_ID;
                $distance_traveled = Race_Stage_Entry::DISTANCE_TRAVELED;
                $distance_unit_arg = Race_Stage_Entry::DISTANCE_UNIT;
                $distance_is_in_kilometers = self::IS_KILOMETERS;

                $trse_selections_html .= <<<FORM_BODY
                    Race stage: <strong>{$race_stage}</strong><br>
                    {$ran_class_html}
                    <div class="border">
                        <div class="hide-overflow disp-flex">
                            <div class="hide-overflow def-pad">
                                <label for="{$distance_traveled}">{$distance_unit_visible}</label>
                                <input required type="number" id="{$distance_traveled}" name="{$distance_traveled}" min="0" step="0.1">\n
                                {$outcomes_html}
                            </div>\n
                        </div>\n
                    </div>\n
                    <input type="hidden" id="{$wc_order_id_arg}" 
                        name="{$wc_order_id_arg}" value="{$wcOrderId}">
                    <input type="hidden" id="{$distance_unit_arg}"
                        name="{$distance_unit_arg}" value="{$distance_unit_value}">
                    <input type="hidden" id="{$distance_is_in_kilometers}"
                        name="{$distance_is_in_kilometers}" value="{$useKilometers}">
                FORM_BODY;
            }

            $race_stage_arg = Race_Stage_Entry::RACE_STAGE_ARG;

            $trse_selections_html .= <<<HIDDEN_PART
                <input type="hidden" id="{$race_stage_arg}" 
                    name="{$race_stage_arg}" value="{$race_stage}">
            HIDDEN_PART;

            $trse_selections_html .= "<br>" . HTML_Help::makeHTMLYesNoOptionString(
                self::$NON_RACING_POINTS_ARGS[self::HOWLADAYS_IDX], 
                __("Did you do any howladays?")) . "<br>";
            $trse_selections_html .= HTML_Help::makeHTMLYesNoOptionString(
                self::$NON_RACING_POINTS_ARGS[self::VOLUNTEERING_IDX], 
                __("Did you volunteer?")) . "<br>";

            //$trse_selections_html .= "</div>\n"; // for border

            $record_to_server = "Record to server.";

            $trse_selections_html .= <<<FORM_END_GAME
                <br>\n
                    <button type="submit">{$record_to_server}</button>\n
                </form>\n
            FORM_END_GAME;

            return $trse_selections_html;
        }

        function checkNonceState() {
            if ("POST" != $_SERVER["REQUEST_METHOD"]) {
                User_Visible_Exception_Thrower::throwErrorCoreException(__("Invalid request race-stage-entry-2"));
            }
            
            if (array_key_exists(self::NONCE_NAME, $_POST)) {
                if (!wp_verify_nonce($_POST[self::NONCE_NAME], self::NONCE_ACTION)) {
                    User_Visible_Exception_Thrower::throwErrorCoreException(__("Security check failed race-stage-entry."));
                }
            } else {
                User_Visible_Exception_Thrower::throwErrorCoreException(__("Nonce not provided race-stage-entry."));
            }
        }

        // Writes out the parameters for the timed path to the database
        function writeTimedPathToMushDB(): string {
            try {
                $this->checkNonceState();

                $hours = -1;
                $minutes = -1;
                $seconds = -1.0;
                $bib_number = 0;
                $wc_product_id = 0;
                $travel_time = -1;

                // timed path
                // Requires: hours, minutes, seconds, bib_number, wc_product id
                $hours = (int)test_number($_POST[self::$TIMED_PATH_QUERY_ARGS[self::HOURS_IDX]]);  
                if ($hours < 0) {
                    self::throw(__LINE__);
                }

                $minutes = (int)test_number($_POST[self::$TIMED_PATH_QUERY_ARGS[self::MINUTES_IDX]]); 
                if ($minutes < 0) {
                    self::throw(__LINE__);
                }

                $seconds = (int)test_number($_POST[self::$TIMED_PATH_QUERY_ARGS[self::SECONDS_IDX]]);
                if ($seconds < 0) {
                    self::throw(__LINE__);
                }
                
                $seconds = round($seconds, 1, PHP_ROUND_HALF_DOWN);

                $travel_time = hoursMinutesSecondsToSecondsF($hours,$minutes,$seconds);

                $bib_number = (int)test_number($_POST[self::$TIMED_PATH_QUERY_ARGS[self::BIB_NUMBER_IDX]]);
                if ($bib_number <= 0) {
                    self::throw(__LINE__);
                }
                            
                $wc_product_id = (int)test_number($_POST[WC_PRODUCT_ID]);
                if ($wc_product_id <= 0) {
                    self::throw(__LINE__);
                }

                $common = $this->getCommonArgs();
            } catch (\Exception $e) {
                return WP_Defs::$GENERIC_INVALID_PARAMETER_MSG;
            }

            $stmt = null;

            try {
            $stmt = (new Mush_DB)->execSql(
                "call sp_updateTRSEByBibNumber(
                    :wcProdId,
                    :bibNumber,
                    :secondsF,
                    :outcome,
                    :raceStage,
                    :runClass)",
                ['wcProdId' => $wc_product_id,
                 'bibNumber' => $bib_number,
                 'secondsF' => $travel_time,
                 'outcome' => $common->outcome,
                 'raceStage' => $common->race_stage,
                 'runClass' => $common->run_class_id],
                $common->user_error_msg);
            } catch (\Exception $e) {
                return WP_Defs::$GENERIC_INVALID_PARAMETER_MSG;
            }

            return $this->cleanup($stmt);
        }

        function cleanup($stmt): string {
            unset($_GET);
            unset($_POST);

            if (is_null($stmt)) {
                return __("The race stage entry could not be recorded.");
            }

            return __("The race entry has been recorded.");
        }

        function validateDistanceRaceQueryArgs() {
            try {
                if (array_key_exists(Race_Stage_Entry::DISTANCE_TRAVELED, $_POST)) {
                    if (array_key_exists(WC_ORDER_ID, $_POST)) {
                        if (array_key_exists(self::IS_KILOMETERS, $_POST)) {
                            if (array_key_exists(WC_ORDER_ID, $_POST)) {
                                if (array_key_exists(Race_Stage_Entry::DISTANCE_UNIT, $_POST)) {
                                    return (self::$non_racing_points->validateQueryArgs());}
                            } else {self::throw(__LINE__);}
                        } else {self::throw(__LINE__);}
                    } else {self::throw(__LINE__);}
                }

                return HTML_Status::STATUS_TRY_NEXT;
            } catch (\Exception $e) {
                var_debug($e);
                error_log(print_r($e, true));
                return WP_Defs::$GENERIC_INVALID_PARAMETER_MSG;
            }
        }

        // params: $_POST
        //  -> Hours, Minutes, Seconds, Race stage, bib_number, product id
        //  or
        //  -> mileage, order id
        //  and
        //  -> Race stage, ran class, and outcome - Enum as string
        function writeDistancePathToMushDB() {
            // TODO: Currentelly we may end up doing this twice.
            //      revisit after the security implications are understood.
            try {
                $this->checkNonceState();

                $distance = -1;
                $wc_order_id = 0;
                $distance_unit = null;
                $isKilometers = null;

                // Distance traveled by bib number
                // Requires: DISTANCE_TRAVELED, WC_ORDER_ID, and DISTANCE_UNiT
                $distance_traveled = test_number($_POST[Race_Stage_Entry::DISTANCE_TRAVELED]);
                if ($distance_traveled < 0) {
                    self::throw(__LINE__);
                }

                $distance = $distance_traveled;

                $wc_order_id = test_number($_POST[WC_ORDER_ID]);
                if ($wc_order_id <= 0) {
                    self::throw(__LINE__);
                }

                $distance_unit = (string)sanitize_text_field($_POST[Race_Stage_Entry::DISTANCE_UNIT]);
                if (!((Units::MILES == $distance_unit) || (Units::KILOMETERS == $distance_unit))) {
                    self::throw(__LINE__);
                }
            
                $isKilometers = (string)sanitize_text_field($_POST[self::IS_KILOMETERS]);
                if (true == $isKilometers)  {
                    $distance_traveled *= Units::KILOMETERS_TO_MILES;
                } else {
                    // No telling what could be in isKilometers
                    if (false != $isKilometers)  {
                        self::throw(__LINE__);
                    }
                }

                $wc_order_id = (int)test_number($_POST[WC_ORDER_ID]);
                if ($wc_order_id <= 0) {
                    self::throw(__LINE__);
                }   

                $common = $this->getCommonArgs();
            } catch (\Exception $e) {
                var_debug($e);
                write_log(print_r($e, true));
                return WP_Defs::$GENERIC_INVALID_PARAMETER_MSG;
            }
            
            if (Units::KILOMETERS == $distance_unit) {
                $distance *= Units::KILOMETERS_TO_MILES;
            } else if (Units::MILES != $distance_unit) {
                return __("Invalid distance unit {$distance_unit}.");}

            $stmt = null;

            try {
                $stmt = (new Mush_DB)->execSql(
                    "call sp_updateTRSEForRSE(
                        :wcOrderId,
                        :distance, 
                        :outcome,  
                        :raceStage, 
                        :runClassId)",
                    ['wcOrderId' => $wc_order_id, 
                    'distance' => $distance, 
                    'outcome' => $common->outcome, 
                    'raceStage' => $common->race_stage, 
                    'runClassId' => $common->run_class_id],
                    $common->user_error_msg);   
            } catch (\Exception $e) {
                return WP_Defs::$GENERIC_INVALID_PARAMETER_MSG;
            }

            return $this->cleanup($stmt);
        }

        // Gets the common args and puts them into a structure
        // Returning a string would tightly bind this function to other functions.
        function getCommonArgs() {
            $common = new Race_Stage_Common();

            if (array_key_exists(Race_Stage_Entry::RACE_STAGE_ARG, $_POST)) {
                $common->race_stage = test_number($_POST[Race_Stage_Entry::RACE_STAGE_ARG]);
                if ($common->race_stage < 0) {
                    return __("Race stage must be greater than 0.");
                }
            } else {
                return __("The race stage must be provided.");
            }

            if (array_key_exists(Race_Stage_Entry::OUTCOME, $_POST)) {
                $common->outcome = sanitize_text_field($_POST[Race_Stage_Entry::OUTCOME]);
                if (!in_array($common->outcome, Race_Stage_Entry::OUTCOMES)) {
                    return __("Invalid race outcome supplied.");
                }

                if ($common->race_stage < 0) {
                    return __("The race stage was not set properly.");
                }
            } else {
                return __("The race outcome must be provided.");
            }
            
            if (array_key_exists(Race_Stage_Entry::RAN_CLASS, $_POST)) {
                $common->run_class_id = test_number($_POST[Race_Stage_Entry::RAN_CLASS]);
                if ($common->run_class_id < 0) {
                    return __("Run class id must be greater than 0");
                }
                if ($common->run_class_id > Teams::MAX_RUN_RACE_CLASSES) {
                    return __("No such run class id.");
                }
            } else {
                return __("The class ran in must be provided.");
            }

            return $common;
        }

        function validateTimedRaceQueryArgs() {
            if (self::$timed_html_help->validateQueryArgs()) {
                if (self::$non_racing_points->validateQueryArgs()) {
                    return true;
                }
            }

            return false;
        }

        // TODO: See if the race was set up to be untimed.
        // Get stage from race information
        function makeHTMLRaceStageEntry() {             
            if (Html_Status::STATUS_DONE == $this->validateTimedRaceQueryArgs()) {
                return $this->writeTimedPathToMushDB();
            } else if (Html_Status::STATUS_DONE == $this->validateDistanceRaceQueryArgs()) {
                return $this->writeDistancePathToMushDB();
            } else if (array_key_exists(WC_PAIR_ARGS, $_GET)) {
                return $this->makeHTMLRaceStageEntryForm();
            }
            
            return $this->makeProductSelectionForm();
        }
    }
?>