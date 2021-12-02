<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . 'includes/units.php';
    //require_once plugin_dir_path(__FILE__) . 'includes/autoloader.php';

    function ironpaws_wp_delete_user_form(\WP_User $current_user) {
        _e("WARNING: All awards, positions, teams, and dogs associated with this user will be destroyed.", 
            WP_Defs::IRONPAWS_TEXTDOMAIN);
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
        const DISTANCE_UNIT = "distance_unit";
        const DISTANCE_UNIT_LABEL = 'select_unit_label';

        static ?array $ironpaws_ers = null;

        static function install() {
            WP_Hooks::init();
            echo _e("Installing IronPaws plugin", WP_Defs::IRONPAWS_TEXTDOMAIN);

            if (file_exists(RACE_RESULTS_DIR)) {
                return;
            }

            mkdir(RACE_RESULTS_DIR, 0644);
        }

        static function init() {
            if (PHP_SESSION_ACTIVE !== session_status()) {
                session_start();
            }
            
            load_plugin_textdomain(WP_Defs::IRONPAWS_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

            self::$ironpaws_ers = ['social_event', 'volunteer_ironpaws_race'];
        }

        static function login() {
            session_destroy ();
        }

        static function logout() {
            session_destroy ();
        }

        static function load_my_own_textdomain( $mofile, $domain ) {
            if ( WP_Defs::IRONPAWS_TEXTDOMAIN === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
                $locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
                $mofile = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/languages/' . $domain . '-' . $locale . '.mo';
            }
            return $mofile;
        }

        // TODO: https://codex.wordpress.org/Customizing_the_Registration_Form
        // Creates an entry that asks for a distancce unit.
        static function registration_form() {
            WP_Hooks::init();
            Units::init();

            $distance_unit = self::DISTANCE_UNIT;
            $distance_unit_user = __("Distance unit:", WP_Defs::IRONPAWS_TEXTDOMAIN);
            $miles = Units::MILES;
            $kilometers = Units::KILOMETERS;
            $miles_user = Units::$MILES_USER;
            $kilometers_user = Units::$KILOMETERS_USER;

            echo <<<REGISTRATION_FORM
            <p>
            <label for="{$distance_unit}">{$distance_unit_user}</label>
                <select name="{$distance_unit}" id="{$distance_unit}">
                    <option value="{$miles}">{$miles_user}</option>
                    <option value="{$kilometers}">{$kilometers_user}</option>
                </select>
             </p>
            REGISTRATION_FORM;
        }

        /** @param: int $user_id -> Id of the user to add
         * @param: array $userData - > array data that was passed in to wp_insert_user
         */ 
        static function user_register(int $user_id) {
            WP_Hooks::init();
            try {
                $distance_unit = self::DISTANCE_UNIT;
                $selected_unit = "miles";

                if (array_key_exists($distance_unit, $_POST)) {
                    $selected_unit = sanitize_text_field($_POST[$distance_unit]);
                }

                $db = new Mush_DB();

                $db->execSql("CALL sp_newPersonUsingWCOrderID(:salutation, :wp_user_id, :distance_unit)", 
                    ['salutation' => null, 'wp_user_id' => $user_id, 'distance_unit' => $selected_unit]);
            } catch(\Exception $e) {
                return User_Visible_Exception_Thrower::throwErrorCoreException(
                    __("Racer information could not be created.", WP_Defs::IRONPAWS_TEXTDOMAIN), 0, $e);
            }

            return null;
        }

        const NRP_IDX_ID_EVENT = 0;
        const NRP_IDX_DATE_COMPLETED = 1;

        const ER_IDX_NAME = 0;
        const ER_IDX_TYPE = 1;

        /** @param: $id -> Id of the user to delete
        * @param: $reassign -> id of the user to reassign posts to
        * @param: $user -> WP_User object of the user that is being deleted
        */
        static function delete_user(int $user_id) {
            WP_Hooks::init();
            
            try {
                Strings::init();

                $db = new Mush_DB();

                $person_id = $db->execAndReturnInt("CALL sp_getPersonDetailsFromUserId(:user_id)", 
                    ['user_id' => $user_id],
                    __("Can't get information about this user ", WP_Defs::IRONPAWS_TEXTDOMAIN) . Strings::$CONTACT_SUPPORT);

                $rows_changed = 0;

                // Get all of the ERs for this person.
                $nrpErRows = $db->execAndReturnRaw("CALL sp_getNRPErId(:person_id)", 
                [':person_id' => $person_id], 
                __('Failure getting all external activities ', WP_Defs::IRONPAWS_TEXTDOMAIN) . Strings::$CONTACT_SUPPORT,
                1);

                if (empty($nrpErRows)) {
                    return;
                }

                $dbConn = $db->getConnection();

                foreach($nrpErRows[0] as $erIdx) {
                    if (empty($erIdx)) {
                        continue;
                    }

                    if (empty($erIdx)) {
                        continue;
                    }

                    // Check to see if there are other contestants that are using the same ER.
                    // TODO: Replace with a SELECT COUNT(*) style SP.
                    $refOfNRPContents =$db->execAndReturnRaw("CALL sp_getNRPsThatContainErRef(:er_idx)", 
                        [':er_idx' => $erIdx],
                        __("Unable to get all external refs instances" . Strings::$CONTACT_SUPPORT));

                    // Don't delete the ER ref if there is allready a reference to it.
                    if (count($refOfNRPContents) > 1) {
                        continue;
                    }

                    /*$erContents = $db->execAndReturnRow("call sp_getERContents(:er_idx)", 
                        [':er_idx' => $erIdx],
                        __("Unable to get details of the external activities of this person."),
                        2);

                    if (in_array($erContents[self::ER_IDX_TYPE], WP_Hooks::$ironpaws_ers)) {
                        continue;
                    }*/

                    // Check to see if nrp_type is of an internal reference. If
                    // true, then don't delete the external ref.
                    $db->execSql("CALL sp_deleteER(':er_idx',@rows_changed)", 
                        ['er_idx' => $erIdx, 'rows_changed' => $rows_changed]);
                }

                $fn1 = function() use ($db, $dbConn, $person_id, $rows_changed) {
                    try {
                        $stmt = $db->statement = $dbConn->prepare("CALL sp_deleteNRP(:person_id,@rows_changed)");
                        $stmt->bindParam(":person_id", $person_id, \PDO::PARAM_INT);
                        $stmt->bindParam("@rows_changed", $rows_changed, \PDO::PARAM_INT | \PDO::PARAM_INPUT_OUTPUT);
                    } catch (\PDOException $e) {return $e;}
                };

                $db->execFn($fn1);

                $db->execSql("CALL sp_deletePerson(:wp_user_id)", ['wp_user_id' => $user_id]);
            } catch(\Exception $e) {
                return User_Visible_Exception_Thrower::throwErrorCoreException(
                    __("Error in deleting racer information. ", WP_Defs::IRONPAWS_TEXTDOMAIN) . Strings::$CONTACT_SUPPORT, 
                    0, 
                    $e);
            }

            return null;
        }
    }
?>