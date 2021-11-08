<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'includes/container-html-pattern.php';
    require_once plugin_dir_path(__FILE__) . 'includes/autoloader.php';
    require_once plugin_dir_path(__FILE__) . 'includes/scoreable.php';
    require_once plugin_dir_path(__FILE__) . 'includes/strings.php';
    require_once plugin_dir_path(__FILE__) . 'includes/util.php';

    use Algorithms\BinaryTree;
    use Algorithms\BinaryNode;

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;
    use stdClass;

    class Race_Results implements Container_HTML_Pattern   {
        protected $rank;
        protected $bib_number;
        protected $team_name;
        protected $unknown;
        protected $left_race;

        protected $target_race_class;

        protected WC_Rest $wc_rest;

        static function do_shortcode() {
            return (new Race_Results())->get();    
        }

        public function __construct() {
            $this->wc_rest = new WC_Rest();
            
            $this->rank = \__("Rank", "ironpaws");
            // translators: The bib goes over the neck and hangs down in front.
            // It will have a number on it identifying the musher.
            $this->bib_number = \__("Bib number", "ironpaws");
            $this->team_name = \__("Team name", "ironpaws");
            $this->mushers_name = \__("Musher's name", "ironpaws");
            $this->class = \__("Class", "ironpaws");
            $this->total_score = \__("Total score", "ironpaws");
            $this->unknown = \__("Unknown", "ironpaws");
            $this->left_race = \__("Left race", "ironpaws");
        }

        function get() {
            // Get all of the orders.
            // See if a file for the $order exists.
            // If the file exists, load it in.
            // If it does not exists, create the html:
            //  write out the the html between the divs to a file
            //  called $order_number.html
            // Sort by bib number, walk, then walk by score and output to html.
            $db = null;
            try {
                $db = new Mush_DB();
            } catch (\PDOException $e) {
                return Strings::$CONTACT_SUPPORT . Strings::$ERROR . 'race-results_connect-1.';
            }

            $race_controllers_by_product_id = [];

            // -Loop through all of the orders
            // -Sort by bib number
            // -Store all of the results in an array of trees.
            // -Walk the array
            //      -walk the trees, calling the callback.
            //      -Compare by mileage(score) or time.
            //      -Insert into the tree, by mileage or time.
            try {
                $all_wc_orders = $this->wc_rest->getAllOrders();

                $race_details_searcher = new Race_Details();

                foreach($all_wc_orders as $wc_order) {   
                    $stmt = $db->execSql("CALL sp_getTRSEScoreValues(:wc_order_id)", 
                        ['wc_order_id' => $wc_order->id]);

                    if (is_null($stmt)) {
                        User_Visible_Exception_Thrower::throwErrorCoreException(
                            __("Internal error race-results-1. Please contact support or file a bug.", 0));
                    }

                    // See if the scorecard is tracked.
                    // If it is tracked, see if it is applicable to the race.
                    // Insert a new scorecard if not tracked for a race
                    // Otherwise, update the score.
                    $trse_table = $stmt->fetchAll(\PDO::FETCH_NUM);
                    $stmt->closeCursor();

                    // For each order, loop through by class, selecting only items that match tath class
                    foreach($trse_table as $row) {
                        $bib_number = $row[TRSE::TRSE_BIB_NUMBER_IDX];

                        if ('completed' != $row[TRSE::TRSE_OUTCOME_IDX]) {
                            continue;
                        }

                        $line_items = $wc_order->line_items;

                        foreach ($line_items as $line_item) {
                            // If we don't have some of the information, add it,
                            // using the product id as the key.
                            $raceController = null;

                            // Associate race info with product ids
                            if (array_key_exists($line_item->product_id, $race_controllers_by_product_id)) {
                                $raceController = $race_controllers_by_product_id[$line_item->product_id];
                            } else {
                                $raceController = new Race_Controller($db, $line_item); 
                                $race_controllers_by_product_id[$line_item->product_id] = $raceController;
                            }

                            if ($raceController->calcCurRaceStage() <= 
                                $raceController->cur_rd_core_info[TRSE::RD_CORE_STAGES]) {
                                    continue;
                            } 

                            $race_details_searcher->bib_number = $bib_number;
                            $race_details_searcher->details = $row;
                            $what_to_add_to_score = $race_details_searcher;
                            
                            $race_class_idx = $row[TRSE::TRSE_RUN_CLASS_IDX];
                            $cur_node = $race_controllers_by_product_id[$line_item->product_id]->all_classes_race_datas[$race_class_idx]->insertOrFetch($race_details_searcher);
                            $cur_race_details = $cur_node->data;
                            if (is_null($cur_race_details->scorecard)) { // new insert?
                                $cur_race_details->scorecard = $raceController->createAScore_Card($cur_race_details);
                                $race_details_searcher = new Race_Details(); // Prime for next
                            }

                            $cur_race_details->scorecard->addToScore($what_to_add_to_score);
                        }
                    } // end: while
                } // end: foreach 
            }
            catch(\Exception $e) { 
                return User_Visible_Exception_Thrower::throwErrorCoreException(__("Error in getting all the score values."), 0, $e);
            }

            $args = new ScoreCard_CallBack_Args($this);
            $result = "";

            $args->callback = array($this, 'bibNumberToScoreBuilder');

            // Generate the scores. Sort by bib number so we can update the
            // score each time we get a new score.
 
            foreach ($race_controllers_by_product_id as $race_controller) {
                $args->race_class_filter = 0;
                $race_controller->applyToAllNodes($args);
            } 
            
            $args->callback = array($this, 'nodeToHTML');

            foreach ($race_controllers_by_product_id as $race_controller) {
                $args->race_class_filter = 0;
                $result .= $race_controller->genHTMLAsString($args);
            } 

            if (empty($result)) {
                echo __("No results yet. Please check later.");
            }
            write_log($result);
            return $result;
        }
        
        // Take the supplied tree and build a tree according to the sort rule supplied in ->data
        function bibNumberToScoreBuilder(Race_Details $race_details, ScoreCard_CallBack_Args $args = null) {
            $args->per_class_race_scores->insertOrFetch($race_details->scorecard);
        }

        function nodeToHTML(Scoreable $scorecard, ScoreCard_CallBack_Args $args) {
            $raceDetails = $scorecard->getDetails();
            $row = $raceDetails->details;

            $this_customers_info = "";
            $args->rank++;
            $team_name_idx = TRSE::TRSE_NAME_TN_IDX;

            // rank | bib | team name | mushers' name | class | score

            try {
                $this_customers_info = $this->wc_rest->getCustomerDetailsByCustomerId($row[TRSE::TRSE_WC_CUSTOMER_ID]);
                if (is_null($this_customers_info)) {
                    $args->result .= "This musher no longer exists.";
                }
            } catch (HttpClientException $e) {
                $responseBody = json_decode($e->getResponse()->getBody());

                if ("woocommerce_rest_invalid_id" == $responseBody) {
                    $args->result .= "Musher id {$row[TRSE::TRSE_WC_CUSTOMER_ID]} no longer exists";
                }
                $args->result .= <<<RACE_RESULTS_ROW
                    <div class="Row">  
                        <div class="Cell"> 
                            {$args->rank} 
                        </div>  
                        <div class="Cell">    
                            {$this->unknown} 
                        </div>  
                        <div class="Cell">  
                            {$this->left_race}
                        </div> 
                        <div class="Cell">  
                            {$this->unknown}
                        </div>  
                        <div class="Cell">
                            {$scorecard->getFormattedScore()}
                        </div>
                    </div> 
                RACE_RESULTS_ROW;

            return;
            }

            $this_customers_info = $this_customers_info->billing;
            $customer_flname = $this_customers_info->first_name . ' ' . $this_customers_info->last_name;

            $args->result .= <<<RACE_RESULTS_ROW
                <div class="Row">  
                    <div class="Cell"> 
                        {$args->rank} 
                    </div>  
                    <div class="Cell">    
                        {$raceDetails->bib_number}  
                    </div>  
                    <div class="Cell">  
                        {$row[$team_name_idx]}
                    </div> 
                    <div class="Cell">  
                        {$customer_flname}
                    </div>  
                    <div class="Cell">
                        {$scorecard->getFormattedScore()}
                    </div>
                </div> 
            RACE_RESULTS_ROW;
            write_log($args->result);
        }
        
        function makeOpeningHTML(?array $params = null) {
            $race_class_name = Teams::RACE_CLASSES[$params[0]][0];

            $result = <<<RACE_RESULTS_START
                <h3>Race class "{$race_class_name}"</h3>
                <div class="Body">
                    <div class="Row">
                        <div class="Cell">
                            {$this->rank}
                        </div>
                        <div class="Cell">
                            {$this->bib_number}
                        </div>
                        <div class="Cell">
                            {$this->team_name}
                        </div>
                        <div class="Cell">
                            {$this->mushers_name}
                        </div>
                        <div class="Cell">
                            {$this->total_score}
                        </div>
                    </div>
            RACE_RESULTS_START;

            return $result;
        }

        // param: $params - > WooCommerce Order Id
        // return: HTML string -> table
        function makeListItemHTML(?array $params = null) {
        }  
           
        function makeClosingHTML(?array $params = null) {
            return "</div>\n";
        }
    }
?>