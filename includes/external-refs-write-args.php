<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class External_Refs_Write_Args {
        public Mush_DB $db;
        public Race_Stage_Common $common;
        public int $person_id = 0;
    }
?>