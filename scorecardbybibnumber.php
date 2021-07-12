<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'includes/comparable.php';

    class ScoreCardByBibNumber implements Comparable {
        use ScoreCard;

        function compareTo(Comparable $other): int {
            return $this->bib_number <=> $other->bib_number;
        }
    }
?>