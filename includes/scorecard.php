<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    trait ScoreCard {
        function __construct($bib_number_arg = 0, $run_row_args = null, $score_arg = -1) {
            $this->bib_number = $bib_number_arg;
            $this->score = $score_arg;
            $this->run_details = $run_row_args;
        }

        public int $bib_number;
        public int $score;
        public ?array $run_details;
    }
?>