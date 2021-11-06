<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once 'comparable.php';

    class Scored_Entry extends Scoreable implements Comparable {
        use ScoreCard;

        function __construct(Race_Details $race_details) {
            $this->init();
            parent::__construct($race_details);
        }

        function addToScore(Race_Details $rd) {
            $this->score += $this->milesToPoints(
                $rd->details[TRSE::TRSE_MILES_TIMESTAMP_IDX], 
                $rd->details[TRSE::TRSE_CLASS_ID_IDX],
                $rd->details[TRSE::TRSE_RUN_CLASS_IDX],
                $rd->details[TRSE::TRSE_OUTCOME_IDX]);
        }

        // Converts miles to points based on how the run went
        function milesToPoints(
            int $miles, 
            int $race_class_arg, 
            int $trse_run_class_arg, 
            string $run_outcome_arg) {
            
            if ($trse_run_class_arg < 0) {
                return 0;
            }

            if (!(TRSE::COMPLETED == $run_outcome_arg) || 
                (TRSE::UNTIMED == $run_outcome_arg)) {
                return 0;
            }

            return $miles * Teams::RACE_CLASSES[$race_class_arg][$trse_run_class_arg + 1];
        }
    }
?>