<?php
        namespace IronPaws;
        
        defined( 'ABSPATH' ) || exit;

        require_once plugin_dir_path(__FILE__) . 'autoload.php';
        require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
        require_once plugin_dir_path(__FILE__) . 'includes/debug.php';

        class Race_Summary implements Container_HTML_Pattern { 
            public WP_User $user;

            public function __construct() {
                $logon_form = ensure_loggedon();
                if (!is_null($logon_form)) {
                    return $logon_form;
                }
        
                $user = wp_get_current_user();
            }

            function makeOpeningHTML() {

            }
            
            function makeListItemHTML(array $row) {

            }
            
            function makeClosingHTML() {
                
            }
    
        }
?>