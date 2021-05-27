<?php
    defined( 'ABSPATH' ) || exit;

    namespace IronPaws;

    class MySql {
        static public $reconnectErrors = [
            1317, // interrupted
            2002, // refused
            2006, // CR_SERVER_GONE_ERROR
            2013  // CR_SERVER_LOST
        ];
    }
?>