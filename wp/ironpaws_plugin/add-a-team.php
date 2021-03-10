<?php
     require_once(MY_PLUGIN_DIR_PATH . "mush_db.php");
     require_once(MY_PLUGIN_DIR_PATH . 'include/wp-defs.php');
     require_once(MY_PLUGIN_DIR_PATH . "include/race_classes.php");

     function do_shortcode_add_a_team() {
        $team = TEAM;
        $race_classes = RACE_CLASSES;

        $add_team_html = <<<ADD_TEAM
        <form method="get" id="new_team_form" action="add-a-team">'
            <label for="{$team}">Team name:</label>
            <input type="text" id="{$team}" name="{$team}"><br>
            <label for="{$race_classes}">Race class:</label>
            <input type="text" id="{$race_classes}" name="{$race_classes}"><br>
            <input type="submit" value="Register my team">
        </form>
        ADD_TEAM;

        return $add_team_html;
     }

     function do_shortcode_write_team_to_db() {
        $teamName_id= 0;
        $team_id = 0;
        $person_id = 0;
        $race_class_id = 0;

        $execSql = "CALL sp_addTeamName (:teamName)";

        if (isset($_SESSION[TEAM]) && isset($_SESSION[RACE_CLASSES])) {
            $teamName = $_SESSION[TEAM] = sanitize_text_field($_GET[TEAM]);
            $race_class_id = $_SESSION[RACE_CLASSES] = sanitize_text_field($_GET[RACE_CLASSES]);
        
            try { 
                $stmt = $db->prepare($execSql);
                $stmt->execute([ 'teamName' => $teamName ]);
                $teamName_id = $db->lastInsertId();
                $stmt = null; 
            }
            catch(PDOException $e) { 
                return ( 'The database returned an error while inserting the team.');
                write_log(__FUNCTION__ . ": produced exception {$e}");
            }
        }

        $wc_customer_id = 0;

        if (isset($_SESSION[WC_ORDER_ID])) {
            init_wc();
            global $woocommerce;
            $results = $woocommerce->get('orders/' . $_SESSION[WC_ORDER_ID]);
            if (NULL == $results) {
              return "Unable to talk to WooCommerce while getting customer information";
            }

            $wc_customer_id = $results['customer_id'];
        }

        $salutation = $_SESSION[SALUTATION];
        $language = $_SESSION[LANGUAGE];

        try { 
            $stmt = $db->prepare("CALL sp_newPersonUsingWCOrderID (:salutation, :wc_customerId, :language)");
            $stmt->execute(array( 'salutation' => $salutation, 'wc_customerId' => $wc_customer_id, 'language' => $language));
            $person_id = $db->lastInsertId();
      
            $stmt = null; 
        }
        catch(PDOException $e) { 
            return ( 'The database returned an error while entering musher details.');
            write_log(__FUNCTION__ . ": produced exception {$e}");
        }

        $team_id = 0
        
        try { 
            $stmt = $db->prepare("CALL sp_createTeamByIds (:team_tn_id, :person_id, :team_class_id)");
            $stmt->execute(array( 'team_tn_id' => $team_id, 'person_id' => $person, 'team_class_id' => $race_classes[$race_class]));
            $team_id = $db->lastInsertId();
      
            $stmt = null; 
        }
        catch(PDOException $e) { 
            return ( 'The database returned an error while entering musher details.');
            write_log(__FUNCTION__ . ": produced exception {$e}");
        }

        try {
            $stmt = $db->prepare("CALL sp_initTRSE(:stage, :team_id, :wc_order_id)");
            $stmt->execute(array( 'stage' => 1, 'team_id' => $team_id, 'wc_order_id' => wc_order_id));
        }
        catch(PDOException $e) { 
            // TODO: Do we refund the money?
            return ( 'The database returned an error finalizing the race entry.');
            write_log(__FUNCTION__ . ": produced exception {$e}");
        }
        
        return "Team $teamName added to the database.";
     }
?>