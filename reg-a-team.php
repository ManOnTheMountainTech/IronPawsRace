<?php
  defined( 'ABSPATH' ) || exit;

  namespace IronPaws;

  require_once plugin_dir_path(__FILE__) . 'mush-db.php';
  require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
  require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
  require_once plugin_dir_path(__FILE__) . "logon.php";
  
  function do_shortcode_reg_a_team() {
    ensure_loggedon();

    $email = EMAIL;
    $first_name = FIRST_NAME;
    $last_name = LAST_NAME;
  
    $teams_selections_html = <<<GET_MUSHER
        <form method="get" id="musher-flname" action="fetch-teams">
          <input type="submit" value="Go">
        </form>
        GET_MUSHER;

    return $teams_selections_html;
  }

  function lookup_dogs_by_musher($db, $musher) {
    return "Looked up";
  }
   
  function get_dog_team_info($db, $teams_selections_html) {
    $dog_team_info = get_dogTeamAssignments($db, $team_names);
    
    /* Given a comma delimited list of dogs, with every dog \' escaped, return
    the teams that the dogs are assigned to 
    @params: $dogNames - A comma seperated list of dogs, with every name escape quoted*/
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
    
      $team_names = test_input($_POST["dogNames"]);
      if (empty($team_names)) {
        return ($team_names . " is not a valid list of dogs.");
      }
    }
      
    return $team_names . " sucessfully looked up.<br>";
  } // end do_shortcode_reg_team
    
  function get_dogTeamAssignments(\PDO $db, $team_names) { 
    $execSql = "CALL sp_getCurrentDogTeamAssignment (:teams)";
    $dog_team_info = "<table>";
    
    try { 
      $stmt = $db->prepare($execSql);
      $stmt->execute([ 'teams' => $team_names ]);
  
      while ($row = $stmt->fetch(\PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
        $dog_team_info .= '<tr><td>' . $row[0] . "<td>" . $row[1] . "<td>" . $row[2] . "</tr>";
      }

      $stmt = null; 
    }
    catch(\PDOException $e) { 
      return ( 'The database returned an error while finding teams for dogs.');
      write_log(__FUNCTION__ . ': produced exception {$e}');
    }
    finally {
      $dog_team_info .= '</table>';
    }
    
    return $dog_team_info;
  }
?>