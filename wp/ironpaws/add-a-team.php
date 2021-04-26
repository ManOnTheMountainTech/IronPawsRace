<?php
    defined( 'ABSPATH' ) || exit;
    // Load wordpress regardless of where it is located. Remember, it could be
    // in any subfolder.
    /*if(!defined('ABSPATH')){
    $pagePath = explode('/wp-content/', dirname(__FILE__));
    include_once(str_replace('wp-content/' , 
            '', 
            $pagePath[0] . 
            '/wp-load.php'));
    }*/

    require_once plugin_dir_path(__FILE__) . 'mush-db.php';
    require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
    require_once plugin_dir_path(__FILE__) . "includes/race_classes.php";
    require_once plugin_dir_path(__FILE__) . "includes/util.php";

     function do_shortcode_add_a_team() {
        if (array_key_exists(TEAM_NAME, $_GET) || array_key_exists(RACE_CLASS, $_GET)) {
            return;
        }

        $team_name = TEAM_NAME;
        $race_class = RACE_CLASS;

        $add_team_html = <<<ADD_TEAM_PRE
        <form method="get" id="new_team_form" action="register-a-new-team">
            <label for="{$team_name}">Team name:</label>
            <input type="text" id="{$team_name}" name="{$team_name}"><br>
            <label for="{$race_class}">Race class:</label><br>
            <select id="{$race_class}" name="{$race_class}"><br>
        ADD_TEAM_PRE;

        global $race_classes;
                
        for ($i = 0; $i < count($race_classes); ++$i) {
            $add_team_html .= '\t<option value="' . $i . '">' . "{$race_classes[$i]}</option>";
        }

        $add_team_html .= <<<ADD_TEAM_POST
            </select>
            <input type="submit" value="Register my team">
        </form>
        ADD_TEAM_POST;

        return $add_team_html;
     }

     function do_shortcode_write_team_to_db() {
        if (array_key_exists(TEAM_NAME, $_GET) && array_key_exists(RACE_CLASS, $_GET)) {
            $teamName_id= 0;
            $team_id = 0;
            $person_id = 0;
            $race_class_id = 0;
            $wc_order_id = 0;

            global $race_classes;
            $race_class_id = test_number($_GET[RACE_CLASS]);

            // We might be called directly, so don't assume set
            if (!isset($_SESSION[WC_CUSTOMER_ID])) {
                if (isset($_GET[WC_ORDER_ID])) {

                    $wc_order_id = test_number($_GET[WC_ORDER_ID]);

                    // Validate the order id
                    init_wc();
                    global $woocommerce;
                    $results = $woocommerce->get('orders/' . $wc_order_id);
                    if (NULL == $results) {
                        return "Unable to talk to WooCommerce while getting customer information";
                    }

                    checkRaceEditable($results);

                    $_SESSION[WC_CUSTOMER_ID] = $results['customer_id'];
                }
            }

            $language = isset($_SESSION[LANGUAGE]) ?: 0x646e; // "en" in ASCII

            if (array_key_exists(SALUTATION, $_SESSION)) {
                $salutation = $_SESSION[SALUTATION]; 
            }
            else {
                $salutation = null;
            }

            $teamName = sanitize_text_field($_GET[TEAM_NAME]);
            
            $db = new MushDB();
                    
            try { 
                $db->connect();

                $teamName_id = $db->queryAndGetInsertId('CALL sp_addTeamName (?)', 
                    [$teamName], 
                    "team name");

                $person_id = $db->queryAndGetInsertId("CALL sp_newPersonUsingWCOrderID (:salutation, :wc_customerId, :language)",
                    ['salutation' => $salutation, 
                    'wc_customerId' => val_or_zero_array(WC_CUSTOMER_ID, $_SESSION), 
                    'language' => $language],
                    "musher");

                $team_id = $db->queryAndGetInsertId("CALL sp_createTeamByIds (:team_tn_id, :person_id, :team_class_id)",
                    array('team_tn_id' => $teamName_id, 'person_id' => $person_id, 'team_class_id' => $race_class_id),
                    "team");

            } catch(PDOException $e) { 
                // TODO: Do we refund the money?
                write_log(__FUNCTION__ . ": produced exception " , $e);
                return ( 'The database returned an error while creating the registering for the race.');
            } catch(MushDBException $e) { 
                write_log(__FUNCTION__ . " produced exception ", $e);
                return ( 'An error occured while saving the team information.' );
            }
     
            $strippedTeamName = stripslashes($teamName);
            return "Team $strippedTeamName added to the database.";
        }
     }
?>