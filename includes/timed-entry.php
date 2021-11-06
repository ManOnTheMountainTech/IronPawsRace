<?php
    namespace IronPaws;

use DateTime;

defined( 'ABSPATH' ) || exit;

    require_once 'comparable.php';
    require_once 'util.php';

    class Timed_Entry extends Scoreable implements Comparable {
        use TimeCard;

        function __construct(Race_Details $race_details) {
            parent::__construct($race_details);
            $this->initTimeCard();
        }

        function addToScore(Race_Details $rd) {
            $this->millis += $rd->details[TRSE::TRSE_MILES_TIMESTAMP_IDX];;
        }
    }
?>