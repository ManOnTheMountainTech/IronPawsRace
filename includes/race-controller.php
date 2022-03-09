<?php
    namespace IronPawsLLC;

    defined( 'ABSPATH' ) || exit;

    use DateTime;

    require_once plugin_dir_path(__FILE__) . 'autoloader.php';
    require_once plugin_dir_path(__FILE__) . '/algorithms/binarytree.php';

    use Algorithms\BinaryTree;

    class Race_Controller{
        // name_ri, ri_race_defs_fk, start_date_time_ri
        public ?array $cur_ri_info = null;

        // stage_rd, rd_master_num_days_per_stage, rd_race_type
        public ?array $cur_rd_core_info = null;
        public ?DateTime $race_start_date_time = null;
        
        public ?array $all_classes_race_datas = null;
        public ?array $all_classes_race_scores = null;
        
        public ?string $race_name = null;

        // Works with both line item and product
        function __construct($mushDBArg, $line_item) {
            $this->race_name = $line_item->name;

            if (isset($line_item->product_id)) {
                $product_id = $line_item->product_id;
            }
            else
                if (isset($line_item->id)) {
                    $product_id = $line_item->id;
                }

            $this->cur_ri_info = $mushDBArg->execAndReturnRow('CALL sp_getAllRaceInstanceInfo(?)',
            [$product_id],
            __("The race information about race {$this->race_name}, id ({$product_id}) is not set up."),
            4);

            $this->race_start_date_time = date_create(
                $this->cur_ri_info[Race_Instance::START_DATE_TIME]);
            $ri_race_defs_fk = $this->cur_ri_info[Race_Instance::RACE_DEFS_FK];

            $this->cur_rd_core_info = $mushDBArg->execAndReturnRow(
                'CALL sp_getRaceDefCoreByRD (?)',
                [$ri_race_defs_fk],
                __("The race definition for {$product_id} is not set up."),
                3);

            // Initialize the race data structure
            $this->all_classes_race_datas = $this->makeRaceDatas();
            $this->all_classes_race_scores = $this->makeRaceDatas();
        }

        protected function makeRaceDatas() {;
            $bin_trees = [Teams::MAX_RACE_CLASSES];

            for ($i = 0; $i < Teams::MAX_RACE_CLASSES; ++$i) {
                $bin_trees[$i] = new BinaryTree();
            }

            return $bin_trees;
        }

        // Creates partial race information about the current race.
        // @param: Mush_DB -> The databse connection to use
        // @param: int -> The WooCommerce product id
        function createAScore_Card(Race_Details $race_Details): Scoreable {
            return (Race_Definition::MILEAGE == $this->cur_rd_core_info[Race_Definition::CORE_RACE_TYPE]) ?
                new Scored_Entry($race_Details) :
                new Timed_Entry($race_Details);
        }

        // Returns the current active race stage.
        // This assumes a 1-week long race time.
        // Please check num_race_stages to seeif this is a timed race.
        // @return: ?int -> If a staged race, the number of race stages
        //          ?int -> null if a timed race
        function calcCurRaceStage(): ?int {
            $elapsed_race_days = (date_create()->diff($this->race_start_date_time))->days;
            return \intdiv($elapsed_race_days, 
                $this->cur_rd_core_info[Race_Definition::CORE_MASTER_NUM_DAYS_PER_STAGE]) + 1;
        }

        // Applys the provided scorecard callback arguments to all nodes in the
        // race datas.
        // @param: ScoreCard_Callback_Args -> $args : The arguments to apply to
        // each node
        function applyToAllNodes(ScoreCard_CallBack_Args $args) {
            for ($i = 0; $i < Teams::MAX_RACE_CLASSES; ++$i) {
                $args->per_class_race_scores = $this->all_classes_race_scores[$i];

                $this->all_classes_race_datas[$i]->walk($args);
                ++$args->race_class_filter;
            }
        }

        function genHTMLAsString(ScoreCard_CallBack_Args $args): string {
            // Now walk the tree, and build a new new tree of the race results.
            $this->all_classes_race_datas = null;
            $result = "<H3>" . $this->race_name . "</H3>\n";
            foreach($this->all_classes_race_scores as $race_score) {
                $result .= $args->HTMLGenerator->makeOpeningHTML([$args->race_class_filter]);
                $args->rank = 0;
                $args->result = "";
                $race_score->walk($args);
                $race_score = null;
                
                $result .= (empty($args->result)) ?
                    __("<em>Race results are hidden until complete.<br>") :
                    $args->result;
                ++$args->race_class_filter;
                $result .= $args->HTMLGenerator->makeClosingHTML();
            }

            return $result;
        }
    }
?>