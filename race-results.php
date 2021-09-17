<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    //require_once plugin_dir_path(__FILE__) . 'includes/autoloader.php';
    require_once plugin_dir_path(__FILE__) . 'includes/strings.php';

    use Algorithms\BinaryTree;
    use Algorithms\BinaryNode;

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;
use stdClass;

class Race_Results implements Container_HTML_Pattern {
        const BIB_NUMBER_IDX = 0;
        const TEAM_NAME_IDX = 5;
        const WC_CUSTOMER_ID_IDX = 6;

        protected WC_Rest $wc_rest;

        static function do_shortcode() {
            return (new Race_Results())->get();    
        }

        public function __construct() {
            $this->wc_rest = new WC_Rest();
        }

        function get() {
            $result = $this->makeOpeningHTML();
            $result .= $this->makeListItemHTML([]);
            $result .= $this->makeClosingHTML();
            write_log($result);
            return $result;
        }

        function makeOpeningHTML() {
            $result = <<<RACE_RESULTS_START
                <div class="Body">
                    <div class="Row">
                        <div class="Cell">
                            Rank
                        </div>
                        <div class="Cell">
                            Bib Number
                        </div>
                        <div class="Cell">
                            Team Name
                        </div>
                        <div class="Cell">
                            Musher's Name
                        </div>
                        <div class="Cell">
                            Class
                        </div>
                        <div class="Cell">
                            Total Score
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

            if ('completed' != $run_outcome_arg) {
                return 0;
            }

            return $miles * Teams::RACE_CLASSES[$race_class_idx_arg][$run_class_idx_arg + 1];
        }

        // param: $params - > WooCommerce Order Id
        // return: HTML string -> table
        function makeListItemHTML(array $params) {
            $db;
            try {
                $db = new Mush_DB();
            } catch (\PDOException $e) {
                return Strings::CONTACT_SUPPORT . Strings::ERROR . 'race-results_connect-1.';
            }

            $product_ids = [];

            // Loop through all of the orders
            try {
                $all_wc_orders = $this->wc_rest->getAllOrders();

                $race_data = new BinaryTree();
                $data_holder = new ScoreCardByScoreAndClass();

                foreach($all_wc_orders as $wc_order) {   
                    $result = "";

                    $stmt = $db->execSql("CALL sp_getTRSEScoreValues(:wc_order_id)", 
                        ['wc_order_id' => $wc_order->id]);

                    if (is_null($stmt)) {
                        User_Visible_Exception_Thrower::throwErrorCoreException("Internal error race-results-1. Please contact support or file a bug.", 0);
                    }

                    // See if the scorecard is tracked.
                    // If it is tracked, see if it is applicable to the race.
                    // Insert a new scorecard if not tracked for a race
                    // Otherwise, update the score.
                    $trse_table = $stmt->fetchAll(\PDO::FETCH_NUM);
                    $stmt->closeCursor();
                    foreach($trse_table as $row) {
                        $bib_number = $row[self::BIB_NUMBER_IDX];
                        
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

                            $cur_node = $race_data->insertOrFetch($data_holder);
                            if (-1 == $cur_node->data->score) { // new insert?
                                $cur_node->data = new ScoreCardByScoreAndClass($bib_number, $row, 0); // don't use placeholder
                            }
                        
                            $cur_scorecard = $cur_node->data;
                            $race_class_idx = $row[TRSE::TRSE_RACE_CLASSES_IDX];

                            $cur_scorecard->score += $this->milesToPoints(
                                $row[TRSE::TRSE_MILES_IDX], 
                                $race_class_idx, 
                                $row[TRSE::RUN_RACE_CLASS_ID_IDX],
                                $row[TRSE::TRSE_OUTCOME_IDX]);
                        }

                    } // end: while
                } // end: foreach 
            }
            catch(\Exception $e) { 
                return User_Visible_Exception_Thrower::getUserMessage($e);
            }

            $args = new ScoreCard_CallBack_Args($this->wc_rest);
            $result = "";

            $race_data->walk(array($this, 'nodeToHTML'), $args);
            return $args->result;
        }

        function nodeToHTML(ScoreCardByScoreAndClass $scorecard, ScoreCard_CallBack_Args $args) {
            $row = $scorecard->run_details;
            $race_class_idx = $row[TRSE::TRSE_RACE_CLASSES_IDX];

            $race_class_description = Teams::RACE_CLASSES[$race_class_idx][0];

            $this_customers_info = "";
            $args->rank++;
            $team_name_idx = self::TEAM_NAME_IDX;

            // rank | bib | team name | mushers' name | class | score

            try {
                $this_customers_info = $this->wc_rest->getCustomerDetailsByCustomerId($row[self::WC_CUSTOMER_ID_IDX]);
                if (is_null($this_customers_info)) {
                    $args->result .= "This musher no longer exists.";
                }
            } catch (HttpClientException $e) {
                $responseBody = json_decode($e->getResponse()->getBody());

                if ("woocommerce_rest_invalid_id" == $responseBody) {
                    $args->result .= "Musher id {$row[self::WC_CUSTOMER_ID_IDX]} no longer exists";
                }
                $args->result .= <<<RACE_RESULTS_ROW
                    <div class="Row">  
                        <div class="Cell"> 
                            {$args->rank} 
                        </div>  
                        <div class="Cell">    
                            Unknown  
                        </div>  
                        <div class="Cell">  
                            Left race
                        </div> 
                        <div class="Cell">  
                            Unknown
                        </div>  
                        <div class="Cell">
                            {$race_class_description}
                        </div>
                        <div class="Cell">
                            {$scorecard->score}
                        </div>
                    </div> 
                RACE_RESULTS_ROW;

            return;
            }

            $this_customers_info = $this_customers_info->billing;
            $customer_flname = $this_customers_info->first_name . ' ' . $this_customers_info->last_name;

            $run_race_id = $row[TRSE::RUN_RACE_CLASS_ID_IDX];

            $args->result .= <<<RACE_RESULTS_ROW
                <div class="Row">  
                    <div class="Cell"> 
                        {$args->rank} 
                    </div>  
                    <div class="Cell">    
                        {$scorecard->bib_number}  
                    </div>  
                    <div class="Cell">  
                        {$row[$team_name_idx]}
                    </div> 
                    <div class="Cell">  
                        {$customer_flname}
                    </div>  
                    <div class="Cell">
                        {$race_class_description}
                    </div>
                    <div class="Cell">
                        {$scorecard->score}
                    </div>
                </div> 
            RACE_RESULTS_ROW;
        }   
        
        function makeClosingHTML() {
            return "</div>";
        }
    }
?>