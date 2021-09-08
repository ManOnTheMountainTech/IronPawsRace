<?php

  namespace IronPaws\Mush;

  defined( 'ABSPATH' ) || exit;



  require_once "wp-defs.php";
  require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
  require_once plugin_dir_path(__FILE__) . 'includes/logon.php';
  require_once plugin_dir_path(__FILE__) . 'includes/autoloader.php';

  function do_shortcode_modify_teams() {
    $logon_form = \ensure_loggedon();
    if (!is_null($logon_form)) {
      return $logon_form;
    }
  }

  function do_shortcode_modify_teams_db() {
      
      $_SESSION[TEAM] = sanitize_text_field($_GET[TEAM]);
      if (empty($_SESSION[TEAM])) {
          write_log(__FUNCTION__ . ': Team name is invalid');
          return "Team $_SESSION[TEAM] is invalid";
      }

      $db = Mush_DB::connect();

      return set_race_team($db, $_SESSION[TEAM]);
  };

  function set_race_team(\PDO $db, $person) { 
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
  
      while ($row = $stmt->fetch(\PDO::FETCH_NUM, \PDO::FETCH_ORI_NEXT)) {
        $teams_selections_html .= '<option value="' . $row[0] . '">' . $row[0] . '</option>';
      }

      $stmt = null; 
    }
    catch(\PDOException $e) { 
      return ( 'The database returned an error while finding teams for dogs.');
      write_log(__FUNCTION__ . ': produced exception {$e}');
    }
    finally {
      $teams_selections_html .= '</select><br><br><input type="submit" value="Go"></form>';
    }
      
    return $teams_selections_html;
  }
?>