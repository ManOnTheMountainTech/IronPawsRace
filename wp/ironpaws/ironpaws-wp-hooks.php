<?php
    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . 'wc-rest.php';
    require_once plugin_dir_path(__FILE__) . 'mush-db.php';

    /** @param: int $user_id -> Id of the user to add
     */ 
    function ironpaws_wp_insert_user(int $user_id) {
        $db = new MushDB();

        // TODO: form for Mr. Field.
        $db->execSql("CALL sp_newPersonUsingWCOrderID(:salutation, :wp_user_id)", 
            ['salutation' => null, 'wp_user_id' => $user_id ]);
    }

    /** @param: $id -> Id of the user to delete
    * @param: $reassign -> id of the user to reassign posts to
    * @param: $user -> WP_User object of the user that is being deleted
    */
    function ironpaws_wp_delete_user(int $id) {
        $db = new MushDB();
        $db->execSql("CALL sp_deletePerson(:wp_user_id)", ['wp_user_id' => $id]);
    }

    function ironpaws_wp_delete_user_form(WP_User $current_user) {
        echo "WARNING: All awards, positions, teams, and dogs associated with this user will be destroyed.";
    }
?>