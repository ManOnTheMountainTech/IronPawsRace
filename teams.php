<?php
  // Load wordpress regardless of where it is located. Remember, it could be
  // in any subfolder.
  defined( 'ABSPATH' ) || exit;

  namespace IronPaws;

  require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
  require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
  require_once plugin_dir_path(__FILE__) . 'wc-rest.php';
  require_once plugin_dir_path(__FILE__) . 'logon.php';;
  require_once plugin_dir_path(__FILE__) . "autoloader.php";

  use Automattic\WooCommerce\Client;
  use Automattic\WooCommerce\HttpClient\HttpClientException;

  abstract class Teams implements Container_HTML_Pattern {
    const NO_SUCH_PERSON_MSG = "Neither an email, nor a first or last name could be processed. Please try again.";

    protected $wp_user = null;

    const RACE_CLASSES = array(
      "Old Dogs Rule",
      "Off the Couch",
      "Canicross",
      "1 Dog Bikejor",
      "2 Dog Bikejor",
      "1 Dog Scooter and Rig",
      "2 Dog Scooter and Rig",
      "3-4 Dog Dryland",
      "1-2 Dog Fatbikejor",
      "1 Dog Skijor",
      "2-3 Dog Skijor",
      "1 Dog Sled",
      "2 Dog Sled",
      "3-4 Dog Sled",
      "5-6 Dog Sled",
      "Unlimited Sled and Rig");

    const TEAM_IDX = 0;
    const TEAM_BIB_NUMBER = 1;
    const TEAM_TN_FK = 2;
    const TEAM_CLASS_ID = 3;
    const TEAM_NAME_ID = 4;
      
    public function __construct() {
      $this->wp_user = wp_get_current_user();
    }

    // Fetch's a musher's teams as HTML.
    // @param: optional: _GET[WC_ORDER_ID] -> WooCommerce order ID
    public function get(string $form_action) { 
      $logon_form = ensure_loggedon();
      if (!is_null($logon_form)) {
        return $logon_form;
      }

      $mush_db = new Mush_DB();
      //$teams_path = plugins_url('modify_teams.php', __FILE__);

      // TODO: Change to 
      //$teams_path = plugins_url('fetch-teams', __FILE__);
      $teams = '<form method="get" id="' . TEAM_NAME_ID . '" action="' 
        . $form_action . '">';

      $teams .= $this->makeTeamsSelectHTML($mush_db);

      return $teams;
    }

    function makeTeamsSelectHTML(Mush_DB $mush_db) {
      $mushers_teams_failed = false;
      $teams;

      try {
        $teams = $this->get_mushers_teams($mush_db);
        if (null == $teams) {
          $mushers_teams_failed = true;  
        }
      }
      catch(Mush_DB_Exception $e) {
        statement_log(__FUNCTION__, __LINE__, "Unable to create db object", $e);
        return $e->userFriendlyMessage;
      }
      catch(Race_Registration_Exception $e) {         
        $error = $e->processRaceAccessCase();
        if (!is_null($error)) {return $error;}
      } catch(Exception $e) {
          write_log(makeHTMLErrorMessage($e->getMessage())); 
      }

      if ($mushers_teams_failed) {
        $this->throwNoDogTeamsFound();
      }

      return $teams;
    }

    function throwNoDogTeamsFound() {
      throw new Race_Registration_Exception( 
        <<<ONLY_REGISTER
        No dog teams found for {$this->wp_user->get('display_name')}.</br>
        <a href="register-a-new-team">Register a new team</a>
      ONLY_REGISTER);
    }
  
    // @params: 
    // @returns: an HTML select table of the mushers's teams. null on failure.
    function get_mushers_teams(Mush_DB $db) { 
      $team_name_id = TEAM_NAME_ID;

      //$container_html = $this->makeOpeningHTML();

      // TODO: Handle both add a team and modify a team.
      $teams_selections_html = $this->makeOpeningHTML();
  
      $people_id = $db->execAndReturnInt(
        "CALL sp_getPersonIdFromWPUserId (?)", 
        [$this->wp_user->ID],
        "Unable to get person id from wp user id={$this->wp_user->ID}");

      if (0 == $people_id) {
        throw new Race_Registration_Exception("Failed to get who this musher is.");
      }

      // TODO: May want to pass in the team and team name IDs
      $stmt = $db->execSql("CALL sp_getAllTeamInfoAndTNByPersonId(?)", [$people_id]);
      $foundATeam = false;

      try {
        while ($team_idxs = $stmt->fetch(\PDO::FETCH_NUM, \PDO::FETCH_ORI_NEXT)) {

          // remember: $TEAMS for add-a-team must be an index.
          $teams_selections_html .= $this->makeListItemHTML($team_idxs);
          $foundATeam = true;
        }

        $stmt->closeCursor();
        $stmt = null; 
      }
      catch(\PDOException $e) { 
        throw new Race_Registration_Exception(
          'The database returned an error while finding teams for dogs.');
      }

      if (!$foundATeam) {
        return null;
      }

      $teams_selections_html .= $this->makeClosingHTML();
      return $teams_selections_html;
    }

    function makeFormCloseHTML() {
      $teams_selections_html = '</select><br><br><button type="submit" value="' . TEAM_NAME_ID . '">Select</form>';
      $teams_selections_html .= <<<REGISTER_TEAM_INSTEAD
        <form action="register-a-new-team">
          <button type="submit">Register a new team instead.</button>
        </form>
      REGISTER_TEAM_INSTEAD;

      return $teams_selections_html;
    }

    function get_race_classes() {

      $race_class_id = RACE_CLASS_ID;

      $teams_selections_html = <<<ADD_TEAM_PRE
        <label for="{$race_class_id}">Race class:</label><br>
        <select id="{$race_class_id}" name="{$race_class_id}"><br>
      ADD_TEAM_PRE;
      
      $i = 0;
      foreach (self::RACE_CLASSES as $race_class) {
          $teams_selections_html .= '\t<option value="' . $i . '">' . "{$race_class}</option>";
          ++$i;
      }
            
      return $teams_selections_html . makeFormCloseHTML();
    }
  }
?>