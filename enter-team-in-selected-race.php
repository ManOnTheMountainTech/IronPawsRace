<?php
    defined( 'ABSPATH' ) || exit;

    require_once(plugin_dir_path(__FILE__) . 'includes/wp-defs.php');
    require_once(plugin_dir_path(__FILE__) . 'includes/debug.php');

    function do_shortcode_write_team_to_db() {
            $team_id = 0;
            
            $db = new Mush_DB();
                    
            try { 
                $db->connect();

                $trse_id = $db->queryAndGetInsertId("CALL sp_initTRSE(:stage, :team_id, :wc_order_id)", 
                    array( 'stage' => 1, 'team_id' => $_SESSION[TEAM_ID], 'wc_order_id' => $wc_order_id),
                    "team race entry");

            } catch(PDOException $e) { 
                // TODO: Do we refund the money?
                write_log(__FUNCTION__ . ": produced exception " , $e);
                return ( 'The database returned an error while creating the registering for the race.');
            } catch(MushDBException $e) { 
                write_log(__FUNCTION__ . " produced exception ", $e);
                return ( $e.message );
            }
?>