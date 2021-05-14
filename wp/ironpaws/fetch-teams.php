<?php
  // Load wordpress regardless of where it is located. Remember, it could be
  // in any subfolder.
  defined( 'ABSPATH' ) || exit;
  session_start(); 

  require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
  require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
  require_once plugin_dir_path(__FILE__) . 'wc-rest.php';
  require_once plugin_dir_path(__FILE__) . 'mush-db.php';
  require_once plugin_dir_path(__FILE__) . 'logon.php';
  require_once plugin_dir_path(__FILE__) . "includes/race_classes.php";

  use Automattic\WooCommerce\Client;
  use Automattic\WooCommerce\HttpClient\HttpClientException;

  class Teams {
    const NO_SUCH_PERSON_MSG = "Neither an email, nor a first or last name could be processed. Please try again.";
    const FORM_INCOMPLETE_MSG = "Not enough information entered.";
    const FORM_INCOMPLETE_ERROR = -1;

    protected $wp_user = null;

    static function do_shortcode_fetch_teams() {
      return (new Teams())->get();
    }

    // Fetch's a musher's teams.
    // @param: optional: _GET[WC_ORDER_ID] -> WooCommerce order ID
    public function get() { 
      try {
        
        $logon_form = ensure_loggedon();
        if (!is_null($logon_form)) {
          return $logon_form;
        }
        
        $this->wp_user = wp_get_current_user();

        $teams = $this->get_mushers_teams(new MushDB());
        if (null == $teams) return <<<ONLY_REGISTER
          No dog teams found for {$this->wp_user->get('display_name')}.</br>
          <a href="register-a-new-team">Register a new team</a>
        ONLY_REGISTER;
      }
      catch(PDOException $e) {
        statement_log(__FUNCTION__, __LINE__, "Unable to create db object", $e);
        return "Unable to connect to the database. Please try again later.";
      }
      catch(WCRaceRegistrationException $e) {          
          $error = $e->processRaceAccessCase();
          if (!is_null($error)) {return $error;}
      } catch(Exception $e) {
        return makeHTMLErrorMessage($e->getMessage());
      }

      return $teams;
    }  

    // @params: 
    // @returns: an HTML select table of the mushers's teams. null on failure.
    function get_mushers_teams(MushDB $db) { 
      //$teams_path = plugins_url('modify_teams.php', __FILE__);

      // TODO: Change to 
      $teams_path = plugins_url('add-a-team', __FILE__);
      $teams_selections_html = '<form method="get" id="' . TEAM_NAME . '" action="' 
        . $teams_path . '">';

      $team_name = TEAM_NAME;
      $race_class = RACE_CLASS;

      // TODO: Handle both add a team and modify a team.
      $teams_selections_html .= <<<'GET_TEAMS'
            <label for="teams">Please select a team to race:</label>
            <select name="teams" id="teams">
        GET_TEAMS;

      $sql = "CALL sp_getMushersTeams (?)";
  
      try { 
        $stmt = $db->execSql($sql, [$this->wp_user->ID]);
        $foundATeam = false;

        while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {

          // remember: $TEAMS for add-a-team must be an index.
          $teams_selections_html .= '<option value="' . $row[0] . '">' . $row[1] . '</option>';
          $foundATeam = true;
        }

        $stmt->closeCursor();
        $stmt = null; 
        
        if (!$foundATeam) {
          return null;
        }
      }
      catch(PDOException $e) { 
        statement_log(__FUNCTION__ , __LINE__ , ': produced exception', $e);
        return ( 'The database returned an error while finding teams for dogs.');
      }

      $teams_selections_html .= "</select></br>";

      global $race_classes;

      $teams_selections_html .= <<<ADD_TEAM_PRE
        <label for="{$race_class}">Race class:</label><br>
        <select id="{$race_class}" name="{$race_class}"><br>
      ADD_TEAM_PRE;
                
      for ($i = 0; $i < count($race_classes); ++$i) {
          $teams_selections_html .= '\t<option value="' . $i . '">' . "{$race_classes[$i]}</option>";
      }
      
      $teams_selections_html .= '</select><br><br><button type="submit" value="' . TEAM_NAME . '">Select</form>';
      $teams_selections_html .= '<button type="submit" formaction="add-a-team">Register a new team instead.</button>';
      
      return $teams_selections_html;
    }
  }
?>