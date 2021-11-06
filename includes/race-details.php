<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class  Race_Details implements Comparable {
        public int $bib_number;
        public ?array $details;
        public ?Scoreable $scorecard;

        function __construct($bib_number_arg = 0, $run_row_args = null) {
            $this->bib_number = $bib_number_arg;

            // Basically this is a row from TRSE
            $this->details = $run_row_args;
            $this->scorecard = null;
        }

        function compareTo(Comparable $other): int {
            return $this->bib_number <=> $other->bib_number;
        }
    }
?>