<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'container-html-pattern.php';
    
    class Race_Results implements Container_HTML_Pattern {
        static function do_shortcode() {
            return (new Race_Results())->get();    
        }

        function get() {

            $result = $this->makeOpeningHTML();
            $result .= $this->makeListItemHTML([64]);
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

        // param: $params - > WooCommerce Order Id
        function makeListItemHTML(array $params) {
            $wc_order_id = $params[0];

            $db = new Mush_DB();

            $race_data = new \SplPriorityQueue();

            try {
                $stmt = $db->query("CALL sp_getAllWCOrders()");
                $all_wc_orders = $stmt->fetchAll(\PDO::FETCH_NUM);
                $stmt->closeCursor();

                $error_message;
      
                $result = "";

                $stmt = $db->execSql("CALL sp_getTRSEScoreValues(:wc_order_id)", 
                    ['wc_order_id' => $wc_order_id]);

                if (is_null($stmt)) {
                    Mush_DB_Exception::throwErrorCoreException("Internal error race-results-1. Please contact support or file a bug.", 0);
                }

                $wc_rest = new WC_Rest();

                while ($row = $stmt->fetch(\PDO::FETCH_NUM, \PDO::FETCH_ORI_NEXT)) {
                    $race_class_description = Teams::RACE_CLASSES[$row[4]][0];

                    $this_customers_info = $wc_rest->getCustomerDetailsByCustomerId($row[6]);

                    $this_customers_info = $this_customers_info->billing;
                    $customer_flname = $this_customers_info->first_name . ' ' . $this_customers_info->last_name;

                    $run_race_id = $row[7];

                    // rank | bib | team name | mushers' name | class | score
                    $result .= <<<RACE_RESULTS_ROW
                        <div class="Row">  
                            <div class="Cell"> 
                                1  
                            </div>  
                            <div class="Cell">    
                                {$row[0]}  
                            </div>  
                            <div class="Cell">  
                                {$row[5]}
                            </div> 
                            <div class="Cell">  
                                {$customer_flname}
                            </div>  
                            <div class="Cell">
                                {$race_class_description}
                            </div>
                            <div class="Cell">
                                112
                            </div>
                        </div> 
                    RACE_RESULTS_ROW;
                }
                $stmt->closeCursor();
            }
            catch(Mush_DB_Exception $e) { 
                statement_log(__FUNCTION__ , __LINE__ , ': produced exception', $e);
                var_dump($e);
                return $e->userHTMLMessage;
            }

            return $result;
        }
        
        function makeClosingHTML() {
            return "</div>";
        }
    }
?>