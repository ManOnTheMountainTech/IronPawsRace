<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
    //require_once plugin_dir_path(__FILE__) . 'includes/autoloader.php';

    /** @param: int $user_id -> Id of the user to add
     */ 
    function ironpaws_user_register(int $user_id) {;
        try {
            $db = new Mush_DB();
            // TODO: form for Mr. Field.
            $db->execSql("CALL sp_newPersonUsingWCOrderID(:salutation, :wp_user_id)", 
                ['salutation' => null, 'wp_user_id' => $user_id ]);
        } catch(\Exception $e) {
            return User_Visible_Exception_Thrower::throwErrorCoreException(
                __("Racer information could not be created."), 0, $e);
        }

        return null;
    }

    /** @param: $id -> Id of the user to delete
    * @param: $reassign -> id of the user to reassign posts to
    * @param: $user -> WP_User object of the user that is being deleted
    */
    function ironpaws_wp_delete_user(int $id) {
        try {
            $db = new Mush_DB();
            $db->execSql("CALL sp_deletePerson(:wp_user_id)", ['wp_user_id' => $id]);
        } catch(\Exception $e) {
            return User_Visible_Exception_Thrower::throwErrorCoreException(
                __("Error in deleting racer information."), 0, $e);
        }

        return null;
    }

    function ironpaws_wp_delete_user_form(\WP_User $current_user) {
        _e("WARNING: All awards, positions, teams, and dogs associated with this user will be destroyed.", 
            "ironpaws");
    }

    function ironpaws_add_loginout_link(string $items, \stdClass $args ) {
        // sub-menu->class
        if (is_user_logged_in() && $args->theme_location == 'primary') {
            $items .= '<li><a href="'. wp_logout_url( get_permalink( \wc_get_page_id( 'myaccount' ) ) ) .'">Log out</a></li>';
        }
            elseif (!is_user_logged_in() && $args->theme_location == 'primary') {
            $items .= '<li><a href="' . get_permalink( \wc_get_page_id( 'myaccount' ) ) . '">Log in</a></li>';
        }
        return $items;
    }  
    
    function ironpaws_wp_load_css() {
        \wp_enqueue_style("ironpaws_rse", 
            plugins_url('/css/race-stage-entry.css', __FILE__));
        \wp_enqueue_style("ironpaws_tables", 
            plugins_url('/css/table.css', __FILE__));
        \wp_enqueue_style("ironpaws_a_href", 
            plugins_url('/css/a_href.css', __FILE__));
    }

    class WP_Hooks {
        static function install() {
            echo _e("Installing IronPaws plugin", "ironpaws");

            if (file_exists(RACE_RESULTS_DIR)) {
                return;
            }

            mkdir(RACE_RESULTS_DIR, 0644);
        }

        static function init() {
            if (PHP_SESSION_ACTIVE !== session_status()) {
                session_start();
            }
            
            load_plugin_textdomain( 'wpdocs_textdomain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }

        static function login() {
            session_destroy ();
        }

        static function logout() {
            session_destroy ();
        }

        static function load_my_own_textdomain( $mofile, $domain ) {
            if ( 'my-domain' === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
                $locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
                $mofile = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/languages/' . $domain . '-' . $locale . '.mo';
            }
            return $mofile;
        }
    }
?>