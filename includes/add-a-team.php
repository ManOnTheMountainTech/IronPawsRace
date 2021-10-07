<?php
    namespace IronPaws;

use Exception;

defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . 'autoloader.php';
    require_once plugin_dir_path(__FILE__) . 'wp-defs.php';
    require_once plugin_dir_path(__FILE__) . 'debug.php';
    require_once plugin_dir_path(__FILE__) . 'verify.php';

    $error_msg = null;

    // Adds a team from scratch
    function do_shortcode_add_a_team() {
        $logon_form = ensure_loggedon();
        if (!is_null($logon_form)) {
            return $logon_form;
        }

        $user = wp_get_current_user();
        $add_team_html = null;

        while(true) {
            if (array_key_exists(TEAM_NAME, $_GET) || array_key_exists(RACE_CLASS_ID, $_GET)) {
                $teamName = sanitize_text_field($_GET[TEAM_NAME]);
                if (is_null($teamName)) {
                    $add_team_html .= __("The provided team name is not usable.<br>", "ironpaws");
                }

                $race_class_id = test_number($_GET[RACE_CLASS_ID]);
                if (!isset(Teams::RACE_CLASSES[$race_class_id][0])) {
                    $add_team_html .= __("Invalid race_class passed in. Please choose again.<br>");
                }

                if (is_null($add_team_html)) { 
                    $db = null;

                    try {
                        $db = new Mush_DB();
                    } catch(\PDOException $e) {
                        return Strings::CONTACT_SUPPORT . Strings::ERROR . 'add-a-team-connect.';
                    }
                        
                    try {                         
                        $person_id = $db->execAndReturnInt(
                            'CALL sp_getPersonIdFromWPUserId (?)',
                            [$user->ID],
                            __("Unfortunately the user id could not be retrieved."));
     
                        $db->execSql("CALL sp_createNewTeam (:team_name, :person_id, :team_class_id)",
                            array('team_name' => $teamName, 'person_id' => $person_id, 'team_class_id' => $race_class_id),
                            "Failed to set the team. Please try again.");

                    } catch(\Exception $e) { 
                        if (MySQL::DUPLICATE_ENTRY == $e->getCode()) {
                            unset($_GET[RACE_CLASS_ID]);
                            unset($_GET[TEAM_NAME]);

                            global $error_msg;
                            $error_msg = <<<HTML
                                <strong>"$teamName" is taken. Please choose another one.</strong>;
                            HTML;

                            continue;
                        }

                        return User_Visible_Exception_Thrower::getUserMessage($e);
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

            $team_name_prompt = __("Team name*:");
            $race_class_prompt = __("Race class*:");

            //$add_team_html .= __("$teamName is taken. Please try again.");

            global $error_msg;
            if ($error_msg) {
                $add_team_html .= $error_msg;
                $error_msg = null;
            }
            $add_team_html .= <<<ADD_TEAM_PRE
            <form required method="get" id="new_team_form" action="register-a-new-team">
                <label for="{$team_name}">{$team_name_prompt}</label>
                <input required type="text" id="{$team_name}" name="{$team_name}"><br>
                <label for="{$race_class_id}">{$race_class_prompt}</label><br>
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