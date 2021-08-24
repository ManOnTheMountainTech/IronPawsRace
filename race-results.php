<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'includes/autoloader.php';
    require_once plugin_dir_path(__FILE__) . 'includes/strings.php';

    use Algorithms\BinaryTree;
    use Algorithms\BinaryNode;

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;
    
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

                // @param $errorCore The "core" of the error message to display.
        // @returns -> the column from the database
        public function execAndReturnColumn(string $statement, 
            array $params,
            string $errorCore) {

            $stmt = $this->execSql($statement, $params);

            if (is_null($stmt)) {
                Mush_DB_Exception::throwErrorCoreException($errorCore, 0);
            }

            $column = $stmt->fetchAll(\PDO::FETCH_NUM);
            $stmt->closeCursor();

            if (is_null($column)) {
                Mush_Db_Exception::throwErrorCoreException($errorCore, 2);
            }

            return $column;
        }

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
        function makeListItemHTML(array $params) {
            //$wc_order_id = $params[0];

            $db;
            try {
                $db = new Mush_DB();
            } catch (\PDOException $e) {
                return Strings::CONTACT_SUPPORT . Strings::ERROR . 'race-results_connect-1.';
            }

            try {
                $stmt = $db->query("CALL sp_getAllWCOrders()");
                $all_wc_orders = $stmt->fetchAll(\PDO::FETCH_NUM);
                $stmt->closeCursor();

                $race_data = new BinaryTree();
                $data_holder = new ScoreCardByScoreAndClass();

                foreach($all_wc_orders as $wc_order) {
                    $error_message;
        
                    $result = "";

                    $stmt = $db->execSql("CALL sp_getTRSEScoreValues(:wc_order_id)", 
                        ['wc_order_id' => $wc_order[0]]);

                    if (is_null($stmt)) {
                        Mush_DB_Exception::throwErrorCoreException("Internal error race-results-1. Please contact support or file a bug.", 0);
                    }

                    // See if the scorecard is tracked.
                    // If it is tracked, see if it is applicable to the race.
                    // Insert a new scorecard if not tracked for a race
                    // Otherwise, update the score.

                    while ($row = $stmt->fetch(\PDO::FETCH_NUM, \PDO::FETCH_ORI_NEXT)) {
                        $cur_musher;

                        $bib_number = $row[self::BIB_NUMBER_IDX];
                        
                        // bib not assigned yet
                        if (is_null($bib_number)) {
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
                    $stmt->closeCursor();
                }

                
            }
            catch(Mush_DB_Exception $e) { 
                statement_log(__FUNCTION__ , __LINE__ , ': produced exception' . var_debug($e));
                return $e->userHTMLMessage;
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
            $bib_number_idx = self::BIB_NUMBER_IDX;
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