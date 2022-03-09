<?php
    namespace IronPawsLLC;
    
    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'autoloader.php';

    class Perf {
        public $nanoTimeStart = 0;

        function startTiming() {
            $this->nanoTimeStart = hrtime(true);
        }

        function returnStats(string $what_was_timed) {
            $time_s = (hrtime(true) - $this->nanoTimeStart) / 1000000000.0;
            return "{$what_was_timed} took {$time_s} seconds.<br>";
        }
    }
?>