<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'mush-db.php';
    require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
    require_once plugin_dir_path(__FILE__) . "teams.php";
    require_once plugin_dir_path(__FILE__) . "includes/util.php";
    require_once plugin_dir_path(__FILE__) . "logon.php";
    require_once plugin_dir_path(__FILE__) . "wc-rest.php";

    // Adds a team from scratch
    function do_shortcode_add_a_team() {
        $logon_form = ensure_loggedon();
        if (!is_null($logon_form)) {
            return $logon_form;
        }

        $user = wp_get_current_user();
        $add_team_html = null;

        if (array_key_exists(TEAM_NAME, $_GET) || array_key_exists(RACE_CLASS_ID, $_GET)) {
            $teamName = sanitize_text_field($_GET[TEAM_NAME]);
            if (is_null($teamName)) {
                $add_team_html .= "The provided team name is not usable.<br>";
            }
            else
                if (is_team_name_taken()) {
                    $add_team_html .= "Team {$teamName} is allready taken. Please choose another.<br>";
                } 

            $race_class_id = test_number($_GET[RACE_CLASS_ID]);
            if (!isset(Teams::RACE_CLASSES[$race_class_id][0])) {
                $add_team_html .= "Invalid race_class passed in. Please choose again.<br>";
            }

            if (is_null($add_team_html)) { 
                $db;

                try {
                    $db = new Mush_DB();
                } catch(\PDOException $e) {
                    return Strings::CONTACT_SUPPORT . Strings::ERROR . 'add-a-team-connect.';
                }
                        
                try { 
                    $teamName_id = $db->execAndReturnInt('CALL sp_addTeamName (?)', 
                        [$teamName], 
                        "The team name could not be set.");

                    $person_id = $db->execAndReturnInt(
                        'CALL sp_getPersonIdFromWPUserId (?)',
                        [$user->ID],
                        "Unfortunately the user id could not be retrieved.");

                    $team_id = $db->execAndReturnInt("CALL sp_createTeamByIds (:team_tn_id, :person_id, :team_class_id)",
                        array('team_tn_id' => $teamName_id, 'person_id' => $person_id, 'team_class_id' => $race_class_id),
                        "Failed to set the team. Please try again.");

                } catch(Mush_DB_Exception $e) { 
                    statement_log(__FUNCTION__, __LINE__, "exception " . print_r($e));
                    return $e->userHTMLMessage;
                }
     
                $strippedTeamName = stripslashes($teamName);

                $reg_a_team = TEAM_REGISTRATION;
                
                // <a href="{$reg_a_team}">Register a team</a>
                $add_team_html .= <<<SUCCESS_MSG
                    <p>
                        Team <strong>$strippedTeamName</strong> added to the database.<br>
                    </p>
                SUCCESS_MSG;

                return $add_team_html;
            }
            
            // Fall through, and show the error above the form.
        }

        $team_name = TEAM_NAME;

        $race_class_id = RACE_CLASS_ID;

        $add_team_html .= <<<ADD_TEAM_PRE
        <form required method="get" id="new_team_form" action="register-a-new-team">
            <label for="{$team_name}">Team name*:</label>
            <input required type="text" id="{$team_name}" name="{$team_name}"><br>
            <label for="{$race_class_id}">Race class*:</label><br>
            <select id="{$race_class_id}" name="{$race_class_id}"><br>
        ADD_TEAM_PRE;

        $add_team_html .= Teams::makeRaceStageHTML();

        $add_team_html .= <<<ADD_TEAM_POST
            </select>
            <input type="submit" value="Register my team">
        </form>
        ADD_TEAM_POST;

        return $add_team_html;
     }


     function is_team_name_taken() {
        return false;
     }

     function write_team_to_db() {
  
        $wc = new WC_Rest();

        if (array_key_exists(TEAM_NAME, $_GET) && array_key_exists(RACE_CLASS_ID, $_GET)) {
            $teamName_id= 0;
            $team_id = 0;
            $person_id = 0;
            $race_class_id = 0;
            $wc_order_id = 0;

            // Verify that they have at least one order
 
            if (array_key_exists(SALUTATION, $_SESSION)) {
                $salutation = $_SESSION[SALUTATION]; 
            }
            else {
                $salutation = null;
            }
        }
     }
?>