<?php
    namespace IronPaws;
    
    defined( 'ABSPATH' ) || exit;

    class Strings { 
        public static $NEXT_STEPS;
        public static $CONTACT_SUPPORT;
        public static $ERROR;
        
        public static $USER_YES;
        public static $USER_NO;

        public static $BAD_ARGUMENTS = null;

        static function get_bad_arguments_msg() {
            if (null === self::$BAD_ARGUMENTS) {
                self::$BAD_ARGUMENTS = __(("Bad argument(s) were passed in."), 'ironpaws');
            }

            return self::$BAD_ARGUMENTS;
        }

        static function init() {
            self::$USER_YES = __('Yes');
            self::$USER_NO = __('No');
            self::$CONTACT_SUPPORT = __("Please contact support or file a bug. ");
            self::$ERROR = __("Error ");
            self::$NEXT_STEPS = '<strong>' . __('Next steps') . '</strong>';
        }
    }
?>