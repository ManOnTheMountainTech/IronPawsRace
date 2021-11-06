<?php
    namespace IronPaws;

    use DateTime;

    defined( 'ABSPATH' ) || exit;

    trait TimeCard {
        public ?float $millis;

        function initTimeCard() {
            $this->millis = 0;
        }

        function getFormattedScore() {
            $hms = secondsFToHMS($this->millis);
            $len = strlen($hms);
            $hms = substr($hms, 0, ($len - 5));
            return $hms;
        }

        function getScore() {
            return $this->millis;
        }
        
        // compareTo:
        // Compares one object to another.
        // @args: $other -> The other object to compare to
        // @returns: -1: less than or equal, 1 if greter than.
        // We want duplicates. To do this, never return 0. Thus, the node with
        // the same score will never be found.
        function compareTo(Comparable $other): int {
            $comparison = $this->millis <=> $other->scorecard->millis;
            return (0 == $comparison) ? 1 : $comparison;
        }
    }
?>