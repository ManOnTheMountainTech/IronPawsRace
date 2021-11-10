<?php
    namespace IronPaws;
    
    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'autoloader.php';

    class Units {
        static $MILES_USER;
        static $KILOMETERS_USER;
        const MILES = "miles";
        const KILOMETERS = "kilometers";
        const MILES_TO_KILOMETERS = 1.609344;
        const KILOMETERS_TO_MILES = 0.609344;

        static function init()
        {
           self::$MILES_USER = __('Miles'); 
           self::$KILOMETERS_USER = __('Kilometers');
        }
    }
?>