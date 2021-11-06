<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    trait ScoreCard {
        // If we use Race_Details at as a trait, then we have to switch whether
        // we are comparing by race details or by score, depending on the tree
        // we are in. With this approach, race_details go in the race details tree,
        // and scores go in the scores tree.
        public float $score = 0;

        function init($score_arg = 0) {
            $this->score = $score_arg;
        }

        function getFormattedScore() {
            return $this->score;
        }

        function getScore() {
            return $this->score;
        }
        
        // compareTo:
        // Compares one object to another.
        // @args: $other -> The other object to compare to
        // @returns: -1: less than or equal, 1 if greter than.
        // We want duplicates. To do this, never return 0. Thus, the node with
        // the same score will never be found.
        function compareTo(Comparable $other): int {
            $comparison = $this->score <=> $other->score;
            return (0 == $comparison) ? -1 : $comparison;
        }
    }
?>