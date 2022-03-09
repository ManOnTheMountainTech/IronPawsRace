<?php
    namespace IronPawsLLC;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'autoloader.php';

    class ScoreCard_CallBack_Args {

        function __construct(Container_HTML_Pattern $HTMLGeneratorArg) {
            $this->result = "";
            $this->HTMLGenerator = $HTMLGeneratorArg;
        }

        public $callback;

        public int $rank = 0;

        public string $result = "";

        public $race_class_filter = 0;

        public $per_class_race_scores = null;

        public Container_HTML_Pattern $HTMLGenerator;
    }
?>