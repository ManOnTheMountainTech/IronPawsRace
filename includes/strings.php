<?php
    namespace IronPaws;
    
    defined( 'ABSPATH' ) || exit;

    class Strings { 
        public static $NEXT_STEPS;
        public static $CONTACT_SUPPORT;
        public static $ERROR;

        static function init() {
            self::$CONTACT_SUPPORT = __("Please contact support or file a bug. ");
            self::$ERROR = __("Error ");
            self::$NEXT_STEPS = '<strong>' . __('Next steps') . '</strong>';
        }
    }
?>