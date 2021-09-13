<?php
    namespace IronPaws;
    
    defined( 'ABSPATH' ) || exit;

    //require_once plugin_dir_path(__FILE__) . 'autoloader.php';

    class HTML_And_Status {

        public string $html;
        
        public $status;

        function __construct(string $html_in = "", int $status_in = 0)
        {
            $html = $html_in;
            $status = $status_in;
        }
    }
?>