<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once 'comparable.php';

    class ScoreCardByScore implements Comparable {

        function __construct(ScoreCardByBibNumber $otherScoreCard) {
            $this->hostScoreCard = $otherScoreCard;
        }

        public ?ScoreCardByBibNumber $hostScoreCard = null;

        // compareTo:
        // Compares one object to another.
        // @args: $other -> The other object to compare to
        // @returns: -1: less than or equal, 1 if greter than.
        // We want duplicates. To do this, never return 0. Thus, the node with
        // the same score will never be found.
        function compareTo(Comparable $other): int {
            $comparison = $this->hostScoreCard <=> $other->hostScoreCard;

            return (0 == $comparison) ? -1 : $comparison;
        }
    }
?>