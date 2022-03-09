<?php
    namespace IronPawsLLC;
    
    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'autoloader.php';

    class SQL_Perf {
        public $nanoTimeStart = 0;
        public $nanoTimeStop = 0;
        public $retries = 0;

        function __construct(Mush_DB $mush_db_host)
        {
            $this->nanoTimeStart = $mush_db_host->nanoTimeStart;
            $this->nanoTimeStop = $mush_db_host->nanoTimeStop;
            $this->retries = $mush_db_host->getReconnectTries();
        }

        function returnStats(string $what_was_timed) {
            $time_s = ($this->nanoTimeStop - $this->nanoTimeStart) / 1000000000.0;
            return "{$what_was_timed} took {$time_s} seconds and {$this->retries} retries.<br>";
        }
    }
?>