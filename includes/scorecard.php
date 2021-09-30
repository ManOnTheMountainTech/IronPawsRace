<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    trait ScoreCard{
        function init($score_arg = -1) {
            $this->score = $score_arg;
        }

        public int $score;
    }
?>