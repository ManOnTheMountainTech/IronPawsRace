<?php
    namespace IronPaws;
    
    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'autoloader.php';

    class Some_Race_Info {
        public $cur_ri_info;
        public $race_start_date_time;
        public $num_race_stages;

        function __construct(Mush_DB $mushDB, int $wcProductId) {
            $this->cur_ri_info = $mushDB->execAndReturnColumn('CALL sp_getAllRaceInstanceInfo(?)',
                [$wcProductId],
                "Race Instance Info is not set up.");
                $this->cur_ri_info = $this->cur_ri_info[0]; // Should only have 1 match from the query

            if (is_null($this->cur_ri_info)) {
                return "The information about race {$wcProductId} is not set up.";
            }

            $this->race_start_date_time = date_create(
                $this->cur_ri_info[TRSE::RI_START_DATE_TIME]);
            $ri_race_defs_fk = $this->cur_ri_info[TRSE::RI_RACE_DEFS_FK];

            $this->num_race_stages = $mushDB->execAndReturnInt(
                'CALL sp_getNumRaceStagesByRD (?)',
                [$ri_race_defs_fk],
                "Unfortunately the number of race stages could not be retrieved.");
        }

        function calcCurRaceStage() {
            $elapsed_race_days = (date_create()->diff($this->race_start_date_time))->days;
            return \intdiv($elapsed_race_days, 7) + 1;
        }
    }
?>