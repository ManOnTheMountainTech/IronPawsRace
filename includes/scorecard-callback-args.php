<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'autoloader.php';

    class ScoreCard_CallBack_Args {
        use ScoreCard;

        function __construct() {
            $this->result = "";
        }

        public int $rank = 0;

        public string $result = "";

        public $race_class_filter = 0;

        public $race_scores = null;
    }
?>