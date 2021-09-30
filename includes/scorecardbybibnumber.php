<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once 'comparable.php';

    class ScoreCardByBibNumber implements Comparable {
        use ScoreCard;

        function __construct($bib_number_arg = -1, $run_row_args = null) {
            $this->init();
            $this->bib_number = $bib_number_arg;

            // Basically this is a row from TRSE
            $this->run_details = $run_row_args;
        }

        public int $bib_number;
        public ?array $run_details;

        function compareTo(Comparable $other): int {
            return $this->bib_number <=> $other->bib_number;
        }
    }
?>