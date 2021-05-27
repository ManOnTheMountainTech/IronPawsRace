<?php
    defined( 'ABSPATH' ) || exit;

    namespace IronPaws;

    require_once plugin_dir_path(__FILE__) . 'mush-db.php';
    require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
    require_once plugin_dir_path(__FILE__) . "includes/race-classes.php";
    require_once plugin_dir_path(__FILE__) . "includes/util.php";
    require_once plugin_dir_path(__FILE__) . "logon.php";
    require_once plugin_dir_path(__FILE__) . "wc-rest.php";

    function do_shortcode_add_a_team() {
        if (array_key_exists(TEAM_NAME, $_GET) || array_key_exists(RACE_CLASS_ID, $_GET)) {
            return;
        }

        $logon_form = ensure_loggedon();
        if (!is_null($logon_form)) {
            return $logon_form;
        }

        $team_name = TEAM_NAME;
        $race_class_id = RACE_CLASS_ID;

        $add_team_html = <<<ADD_TEAM_PRE
        <form method="get" id="new_team_form" action="register-a-new-team">
            <label for="{$team_name}">Team name:</label>
            <input type="text" id="{$team_name}" name="{$team_name}"><br>
            <label for="{$race_class_id}">Race class:</label><br>
            <select id="{$race_class_id}" name="{$race_class_id}"><br>
        ADD_TEAM_PRE;
                
        for ($i = 0; $i < count(Teams::race_classes); ++$i) {
            $add_team_html .= '\t<option value="' . $i . '">' . "{race_classes[$i]}</option>";
        }

        $add_team_html .= <<<ADD_TEAM_POST
            </select>
            <input type="submit" value="Register my team">
        </form>
        ADD_TEAM_POST;

        return $add_team_html;
     }

     function do_shortcode_write_team_to_db() {
        $user = wp_get_current_user();
        $wc = new WC_Rest();

        if (array_key_exists(TEAM_NAME, $_GET) && array_key_exists(RACE_CLASS_ID, $_GET)) {
            $teamName_id= 0;
            $team_id = 0;
            $person_id = 0;
            $race_class_id = 0;
            $wc_order_id = 0;

            $race_class_id = test_number($_GET[RACE_CLASS_ID]);

            // Verify that they have at least one order
 
            if (array_key_exists(SALUTATION, $_SESSION)) {
                $salutation = $_SESSION[SALUTATION]; 
            }
            else {
                $salutation = null;
            }

            $teamName = sanitize_text_field($_GET[TEAM_NAME]);
            
            $db = new Mush_DB();
                    
            try { 
                $db->connect();

                $teamName_id = $db->execAndReturnInt('CALL sp_addTeamName (?)', 
                    [$teamName], 
                    "team name");

                $person_id = $db->execAndReturnInt(
                    'CALL sp_getPersonIdFromWPUserId (?)',
                    [$user->ID],
                    "wc_customer_id");

                $team_id = $db->execAndReturnInt("CALL sp_createTeamByIds (:team_tn_id, :person_id, :team_class_id)",
                    array('team_tn_id' => $teamName_id, 'person_id' => $person_id, 'team_class_id' => $race_class_id),
                    "team");

            } catch(\PDOException $e) { 
                // TODO: Do we refund the money?
                write_log(__FUNCTION__ . ": produced exception " , $e);
                return ( 'The database returned an error while creating the registering for the race.');
            } catch(Mush_DB_Exception $e) { 
                write_log(__FUNCTION__ . " produced exception ", $e);
                return ( 'An error occured while saving the team information.' );
            }
     
            $strippedTeamName = stripslashes($teamName);
            return "Team $strippedTeamName added to the database.";
        }
     }
?>