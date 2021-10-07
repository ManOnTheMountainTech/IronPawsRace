<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'includes/container-html-pattern.php';
    require_once plugin_dir_path(__FILE__) . 'includes/autoloader.php';
    require_once plugin_dir_path(__FILE__) . 'includes/strings.php';

    use Algorithms\BinaryTree;
    use Algorithms\BinaryNode;

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;
    use stdClass;

class Race_Results implements Container_HTML_Pattern {
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

        function makeRaceDatas() {;
            $bin_trees = [Teams::MAX_RACE_CLASSES];

            for ($i = 0; $i < Teams::MAX_RACE_CLASSES; ++$i) {
                $bin_trees[$i] = new BinaryTree();
            }

            return $bin_trees;
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
                return Strings::CONTACT_SUPPORT . Strings::ERROR . 'race-results_connect-1.';
            }

            $product_ids = [];

            // -Loop through all of the orders
            // -Store all of the results in an array of trees.
            // -Compare by mileage.
            // -Walk the array
            //      -walk the trees, calling the callback.
            try {
                $all_wc_orders = $this->wc_rest->getAllOrders();

                // Initialize the race data structure
                $race_datas = $this->makeRaceDatas();
                $data_holder = new ScoreCardByBibNumber();

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
                        $bib_number = $row[TRSE::TEAM_BIB_NUMBER];
                        
                        // bib not assigned yet
                        if (is_null($bib_number)) {
                            continue;
                        }

                        $line_items = $wc_order->line_items;

                        foreach ($line_items as $line_item) {
                            // If we don't have some of the information, add it,
                            // using the product id as the key.
                            $some_info = null;

                            if (array_key_exists($line_item->product_id, $product_ids)) {
                                $some_info = $product_ids[$line_item->product_id];
                            } else {
                                $some_info = new Some_Race_Info($db, $line_item->product_id); 
                                $product_ids[$line_item->product_id] = $some_info;
                            }

                            if ($some_info->calcCurRaceStage() <= $some_info->num_race_stages) {
                                continue;
                            }

                            $data_holder->bib_number = $bib_number;
                            $data_holder->run_details = $row;
                            $race_class_idx = $row[TRSE::TEAM_CLASS_ID];

                            $cur_node = $race_datas[$race_class_idx]->insertOrFetch($data_holder);
                            $cur_scorecard = $cur_node->data;
                            if (-1 == $cur_scorecard->score) { // new insert?
                                $cur_scorecard->score = 0; // don't use placeholder
                                $data_holder = new ScoreCardByBibNumber(); // release reference
                            }
                    
                            $cur_scorecard->score += $this->milesToPoints(
                                $row[TRSE::MILES_TRSE], 
                                $race_class_idx, 
                                $row[TRSE::RUN_CLASS_ID],
                                $row[TRSE::OUTCOME_TRSE]);
                        }
                    } // end: while
                } // end: foreach 
            }
            catch(\Exception $e) { 
                return User_Visible_Exception_Thrower::throwErrorCoreException(__("Error in getting all the score values."));
            }

            $args = new ScoreCard_CallBack_Args();
            $result = "";
            $args->race_scores = $race_scores = $this->makeRaceDatas();

            // Generate the scores. Sort by bib number so we can update the
            // score each time we get a new score.
            foreach($race_datas as $race_data) {
                $race_data->walk(array($this, 'bibNumberToScoreBuilder'), $args);
                ++$args->race_class_filter;
            }

            $args->race_class_filter = 0;

            // Now walk the tree, and build a new new tree of the results.
            foreach($race_scores as $race_score) {
                $result .= $this->makeOpeningHTML([$args->race_class_filter]);
                $race_score->walk(array($this, 'nodeToHTML'), $args);
                //error_log(print_r($args->result, true));
                
                $result .= (empty($args->result)) ?
                    __("<em>Race results are hidden until complete.<br>") :
                    $args->result;
                $args->result = "";
                ++$args->race_class_filter;
                $result .= $this->makeClosingHTML();
            }

            //$result .= $this->makeListItemHTML([]);

            write_log($result);
            return $result;
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

        // Converts miles to points based on how the run went
        function milesToPoints(
            int $miles, 
            int $race_class_idx_arg, 
            int $run_class_idx_arg, 
            string $run_outcome_arg) {
            
            if ($run_class_idx_arg < 0) {
                return 0;
            }

            if (!(TRSE::COMPLETED == $run_outcome_arg) || 
                (TRSE::UNTIMED == $run_outcome_arg)) {
                return 0;
            }

            return $miles * Teams::RACE_CLASSES[$race_class_idx_arg][$run_class_idx_arg + 1];
        }

        // param: $params - > WooCommerce Order Id
        // return: HTML string -> table
        function makeListItemHTML(?array $params = null) {
        }   
        
        // Take the supplied tree and build a tree according to the sort rule supplied in ->data
        function bibNumberToScoreBuilder(ScoreCardByBibNumber $scorecard, ScoreCard_CallBack_Args $args = null) {
            $race_class_idx = $scorecard->run_details[TRSE::TEAM_CLASS_ID];

            $newScorecard = new ScoreCardByScore($scorecard);

            $cur_node = ($args->race_scores[$race_class_idx])->insertOrFetch($newScorecard);;
            if (-1 == $cur_node->data->hostScoreCard->score) { // new insert?
                User_Visible_Exception_Thrower::throwErrorCoreException(__("Error: ") 
                    . 'race-result-1' . __(' Please contact support.'));
            }
        }

        function nodeToHTML(ScoreCardByScore $scorecard, ScoreCard_CallBack_Args $args) {
            $host_score_card = $scorecard->hostScoreCard;
            $row = $host_score_card->run_details;

            $this_customers_info = "";
            $args->rank++;
            $team_name_idx = TRSE::NAME_TN;

            // rank | bib | team name | mushers' name | class | score

            try {
                $this_customers_info = $this->wc_rest->getCustomerDetailsByCustomerId($row[TRSE::WC_CUSTOMER_ID]);
                if (is_null($this_customers_info)) {
                    $args->result .= "This musher no longer exists.";
                }
            } catch (HttpClientException $e) {
                $responseBody = json_decode($e->getResponse()->getBody());

                if ("woocommerce_rest_invalid_id" == $responseBody) {
                    $args->result .= "Musher id {$row[TRSE::WC_CUSTOMER_ID]} no longer exists";
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
                            {$host_score_card->score}
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
                        {$host_score_card->bib_number}  
                    </div>  
                    <div class="Cell">  
                        {$row[$team_name_idx]}
                    </div> 
                    <div class="Cell">  
                        {$customer_flname}
                    </div>  
                    <div class="Cell">
                        {$host_score_card->score}
                    </div>
                </div> 
            RACE_RESULTS_ROW;
            error_log($args->result);
        }   
        
        function makeClosingHTML(?array $params = null) {
            return "</div>\n";
        }
    }
?>