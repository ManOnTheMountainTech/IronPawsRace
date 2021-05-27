<?php
    defined( 'ABSPATH' ) || exit;

    namespace IronPaws;

    require_once plugin_dir_path(__FILE__) . '../includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . '../includes/debug.php';
    require_once plugin_dir_path(__FILE__) . '../mush-db.php';

    class Sprocs_Tests {
        protected Mush_DB $db;

        function __constructor() {
            $db = Mush_DB();

            runTests();
        }

        function runTests() {
            $db->execSQL("CALL sp_getRaceInstance(?)", [14]);
        }
    }
?>