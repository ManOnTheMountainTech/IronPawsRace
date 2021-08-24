<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . 'wc-rest.php';
    require_once plugin_dir_path(__FILE__) . 'mush-db.php';

    /** @param: int $user_id -> Id of the user to add
     */ 
    function ironpaws_user_register(int $user_id) {;
        try {
            $db = new Mush_DB();
        } catch(\PDOException $e) {
            return Strings::CONTACT_SUPPORT . Strings::ERROR . 'shortcode-usr_register.';
        }

        // TODO: form for Mr. Field.
        $db->execSql("CALL sp_newPersonUsingWCOrderID(:salutation, :wp_user_id)", 
            ['salutation' => null, 'wp_user_id' => $user_id ]);
    }

    /** @param: $id -> Id of the user to delete
    * @param: $reassign -> id of the user to reassign posts to
    * @param: $user -> WP_User object of the user that is being deleted
    */
    function ironpaws_wp_delete_user(int $id) {
        try {
            $db = new Mush_DB();
        } catch(\PDOException $e) {
            return Strings::CONTACT_SUPPORT . Strings::ERROR . 'shortcode-delete_user.';
        }
        $db->execSql("CALL sp_deletePerson(:wp_user_id)", ['wp_user_id' => $id]);
    }

    function ironpaws_wp_delete_user_form(\WP_User $current_user) {
        echo "WARNING: All awards, positions, teams, and dogs associated with this user will be destroyed.";
    }

    function ironpaws_add_loginout_link(string $items, \stdClass $args ) {
        if (is_user_logged_in() && $args->theme_location == 'primary') {
            $items .= '<li><a href="'. wp_logout_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ) .'">Log out</a></li>';
        }
            elseif (!is_user_logged_in() && $args->theme_location == 'primary') {
            $items .= '<li><a href="' . get_permalink( wc_get_page_id( 'myaccount' ) ) . '">Log in</a></li>';
        }
        return $items;
    }  
    
    function ironpaws_wp_load_css() {
        \wp_enqueue_style("ironpaws_rse", 
            plugins_url('/css/race-stage-entry.css', __FILE__));
        \wp_enqueue_style("ironpaws_tables", 
            plugins_url('/css/table.css', __FILE__));
    }

    class IronPaws_WP_Hooks {
        static function install() {
            echo "Installing IronPaws plugin";
        }
    }
?>