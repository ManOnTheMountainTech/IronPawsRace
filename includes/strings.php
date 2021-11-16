<?php
    namespace IronPaws;
    
    defined( 'ABSPATH' ) || exit;

    class Strings { 


        public static $NEXT_STEPS;
        public static $CONTACT_SUPPORT;
        public static $ERROR;
        
        public static $USER_YES;
        public static $USER_NO;

        static function init() {
            self::$USER_YES = __('Yes');
            self::$USER_NO = __('No');
            self::$CONTACT_SUPPORT = __("Please contact support or file a bug. ");
            self::$ERROR = __("Error ");
            self::$NEXT_STEPS = '<strong>' . __('Next steps') . '</strong>';
        }
    }
?>