<?php
  namespace IronPaws;

  defined( 'ABSPATH' ) || exit;

  require_once 'wp-defs.php';
  require_once 'debug.php';
  require_once 'logon.php';;
  require_once "autoloader.php";

  use Automattic\WooCommerce\Client;
  use Automattic\WooCommerce\HttpClient\HttpClientException;

  abstract class Teams implements Container_HTML_Pattern {
    const NO_SUCH_PERSON_MSG = "Neither an email, nor a first or last name could be processed. Please try again.";

    protected $wp_user = null;

    const RACE_CLASSES = [
      ["1 Dog skijor",	    1,	  0.5,	0.5,	0.5,	0.5,	0.5,	0.5,	0.5],	
      ["2 Dog skijor",	    1,	  0.5,	0.5,	0.5,	0.5,	0.5,	0.5,	0.5],	// 1		
      ["3 Dog Skijor",	    1,	  0.5,	0.5,	0.5,	0.5,	0.5,	0.5,	0.5],						
      ["1 Dog sled",	      0.5,	0.5,	1,	  0.5,	0.5,	0.5,	0.5,	0.5], // 3				
      ["2 Dog sled",	      0.5,	0.5,	1,	  0.5,	0.5,	0.5,	0.5,	0.5],				
      ["3-4 Dog sled",	    0.5,	0.5,	1,	  0.5,	0.5,	0.5,	0.5,	0.5], // 5				
      ["4 Dog skijor",      1,	  0.5,	0.5,	0.5,	0.5,	0.5,	0.5,	0.5],									
      ["5-6 Dog sled",	    0.5,	0.5,	1,	  0.5,	0.5,	0.5,	1,	  0.5], // 7				
      ["Unlimited dog sled",0.5,	0.5,	1,	  0.5,	0.5,	0.5,	1,	  0.5],		
      ["Canicross", 	      0.5,	0,	  0,	  1,	  1,	  0,	  0,	  0],  // 9			
      ["3-4 Dog dryland",	  0.5,	0.5,	0.5,	0.5,	1,	  1,	  1,	  1],				
      ["1 Dog fatbikejor",	0.5,  0.5,	0.5,	0.5,	0,	  0,	  0,	  1,	1.5,	1.5,	1.5, 1.0], // 11
      ["2 Dog fatbikejor",	0.5,	0.5,	0.5,	0.5,	0,	  0,	  0,	  1,	1.5,	1.5,	1.5, 1.0], // 12
      ["1 Dog bikejor",	    0.5,	0.5,	0.5,	0.5,	1,	  1,	  0.5,	0.5],				
      ["2 Dog bikejor",	    0.5,	0.5,	0.5,	0.5,	1,	  1,	  0.5,	0.5],	// 14			
      ["1 Dog scooter",	    0.5,	0.5,	0.5,	0.5,	1,	  0.5,	1,	  1],				
      ["2 Dog scooter",	    0.5,	0.5,	0.5,	0.5,	1,	  0.5,	1,	  1],  // 16
      ["Old dogs rule",     1,    1,    1,    1,    1,    1,    1,    1],
      ["Off the couch",     1,    1,    1,    1,    1,    1,    1,    1]];  // 18
      
    const MAX_RACE_CLASSES = 18;

    // These are the categories that are listed in the "{x} points for skijor,
    // ..." These are the coumn headers for the points matrix.
    const RUN_RACE_CLASSES = [
      "Skijor",
      "FatBikeJor", // 1
      "Sled",
      "Snowshoe", // 3
      "Canicross",
      "BikeJor",  // 5
      "Cart",
      "Scooter",  // 7
      "FatBike dryland", //8
      "FatBike snow", //9
      "FatBike ice",
      "FatBike sand"
    ];

    const MAX_NON_FATBIKEJOR_CLASSES = 8;
    const MAX_RUN_RACE_CLASSES = 12;
    const RUN_CLASSES_FATBIKEJOR_IDXS = [8, 9];

    const TEAM_IDX = 0;
    const TRSE_BIB_NUMBER_IDX = 1;
    const TEAM_TN_FK = 2;
    const TRSE_CLASS_ID_IDX = 3;
    const TEAM_NAME_ID = 4;
      
    public function __construct() {
      $this->wp_user = wp_get_current_user();
    }

    // Outer envolope of a teams selection form.
    // @param-> $form_action -> the action (web page) to go to on 
 
    public function get(string $form_action) { 
      $logon_form = ensure_loggedon();
      if (!is_null($logon_form)) {
        return $logon_form;
      }

      $mush_db = null;

      try {
        $mush_db = new Mush_DB();
      } catch (\PDOException $e) {
        return Strings::$CONTACT_SUPPORT . Strings::$ERROR . 'teams-connect.';
      }
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
      $teams = null;

      try {
        $teams = $this->get_mushers_teams($mush_db);
        if (null == $teams) {
          $mushers_teams_failed = true;  
        }
      }
      catch(Race_Registration_Exception $e) {         
        $error = $e->processRaceAccessCase();
        if (!is_null($error)) {return $error;}
      } catch(\Exception $e) {
          write_log(makeHTMLErrorMessage($e->getMessage())); 
      }
      catch(\Exception $e) {
        return User_Visible_Exception_Thrower::getUserMessage($e);
      }

      if ($mushers_teams_failed) {
        return $this->htmlNoDogTeamsFound();
      }

      return $teams;
    }

    function htmlNoDogTeamsFound() {
      $icon = plugins_url('ironpaws/img/icons/dogs/sleds/noun_hard_work_1154847.svg');
      $link = plugins_url('ironpaws/register-a-new-team');
      $next_steps = Strings::$NEXT_STEPS;

      return 
        <<<ONLY_REGISTER
          <p>No dog teams found for {$this->wp_user->get('display_name')} ('{$this->wp_user->get('user_login')}').</p>
          <p>$next_steps</p>
          <a href="$link" class="img-a">
            <img 
              src="{$icon}" 
              alt="A musher pulling their dog on a sled">
            <p class="p-aligned">Register a new team</p>
          </a>
      ONLY_REGISTER;
    }
  
    // @params: 
    // @returns: an HTML select table of the mushers's teams. null on failure.
    // @throws: Race_Registration_Exception on error
    function get_mushers_teams(Mush_DB $db) { 
      $team_name_id = TEAM_NAME_ID;

      //$container_html = $this->makeOpeningHTML(?array $params = null);

      // TODO: Handle both add a team and modify a team.
      $teams_selections_html = $this->makeOpeningHTML();

      try {
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
      } catch(\Exception $e) {
        return User_Visible_Exception_Thrower::getUserMessage($e);
      }

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
      catch(\Exception $e) {
        throw new Race_Registration_Exception(
          User_Visible_Exception_Thrower::getUserMessage($e));
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
      
      $teams_selections_html .= $this->makeRaceStageHTML();
            
      return $teams_selections_html . $this->makeFormCloseHTML();
    }

    static function makeRaceStageHTML() {   
      $retHTML = "";
      
      for($i = 0; $i < Teams::MAX_RACE_CLASSES; $i++) {
          $race_class = Teams::RACE_CLASSES[$i][0];
          $retHTML .= '<option value="' . $i . '">' . "{$race_class}</option>";
      }

      return $retHTML;
    }

    static function makeRunRaceClassesHTML(int $registered_race_class) {   
      $retHTML = "";

      $num_run_race_classes_for_race_class = (in_array($registered_race_class, 
        self::RUN_CLASSES_FATBIKEJOR_IDXS)) ? self::MAX_RUN_RACE_CLASSES : self::MAX_NON_FATBIKEJOR_CLASSES;
      
      for($i = 0; $i < $num_run_race_classes_for_race_class; $i++) {
          $run_class = self::RUN_RACE_CLASSES[$i];

          // Index 0 will be the row headers of the points matrix
          $retHTML .= '<option value="' . ($i + 1) . '">' . "{$run_class}</option>\n";
      }

      return $retHTML;
    }
  }
?>