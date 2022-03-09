<?php
    namespace IronPawsLLC;

    defined( 'ABSPATH' ) || exit;

    abstract class Scoreable implements Comparable{
        abstract function getScore();

        // Gets the score such that a human can understand it
        abstract function getFormattedScore();
        abstract function addToScore(Race_Details $rd);

        function __construct($details) {
            $this->raceDetails = $details;
        }

        protected $raceDetails = null;

        function getDetails(): Race_Details {
            return $this->raceDetails;
        }

        function setDetails(Race_Details $details) {
            $this->raceDetails = $details;
        }
    }
?>