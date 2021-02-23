<?php
    require_once("wp-defs.php");
    require_once(plugin_dir_path(__FILE__) . 'includes/debug.php');

    function do_shortcode_fetch_teams() {
        
        $_SESSION[MUSHER] = sanitize_text_field($_GET[MUSHER]);
        if (empty($_SESSION[MUSHER])) {
            write_log(__FUNCTION__ . ': Musher name is invalid');
            return "Musher $_SESSION[MUSHER] is invalid";
        }

        $db = MushDB::connect();

        return get_mushers_team($db, $_SESSION[MUSHER]);
    };

    function get_mushers_team(PDO $db, $person) { 
        $teams_path = plugins_url('modify_teams.php', __FILE__);
        $teams_selections_html = '<form method="get" id="team" action="' 
        . $teams_path . '">';
    
        $teams_selections_html .= <<<'GET_TEAMS'
              <label for="teams">Please select a team to race:</label>
              <select name="teams" id="teams">
          GET_TEAMS;

        $execSql = "CALL sp_getMushersTeams (:person)";
        //$person .= '"' + $person + '"';
        
        try { 
          $stmt = $db->prepare($execSql);
          $stmt->execute([ 'person' => $person ]);
      
          while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
            $teams_selections_html .= '<option value="' . $row[0] . '">' . $row[0] . '</option>';
          }
    
          $stmt = null; 
        }
        catch(PDOException $e) { 
          return ( 'The database returned an error while finding teams for dogs.');
          write_log(__FUNCTION__ . ': produced exception {$e}');
        }
        finally {
          $teams_selections_html .= '</select><br><br><input type="submit" value="Go"></form>';
        }
        
        return $teams_selections_html;
      }
?>