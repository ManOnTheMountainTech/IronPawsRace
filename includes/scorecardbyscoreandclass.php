<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once 'comparable.php';

    class ScoreCardByScoreAndClass implements Comparable {
        use ScoreCard;

        function compareTo(Comparable $other): int {
            $result = $this->score <=> $other->score;
            if (0 == $result) {
                return $result;
            }

            return ($this->run_details[TRSE::TRSE_RACE_CLASSES_IDX] <=> 
                $other->run_details[TRSE::TRSE_RACE_CLASSES_IDX]);
        }
    }
?>