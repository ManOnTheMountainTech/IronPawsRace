<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class MySql {
        static public $reconnectErrors = [
            1317, // interrupted
            2002, // refused
            2006, // CR_SERVER_GONE_ERROR "There is no active transaction"
            2013  // CR_SERVER_LOST
        ];

        const DUPLICATE_ENTRY = 23000;
    }
?>