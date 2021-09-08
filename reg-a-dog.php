<?php
  namespace IronPaws;

  defined( 'ABSPATH' ) || exit;

  require_once plugin_dir_path(__FILE__) . 'includes/strings.php';
  //require_once plugin_dir_path(__FILE__) . 'includes/dogdefs.php';
  require_once plugin_dir_path(__FILE__) . 'includes/autoloader.php';
  require_once plugin_dir_path(__FILE__) . 'includes/verify.php';

  class Reg_A_Dog {

    static function do_shortcode() {
      /*$logon_form = ensure_loggedon();
      if (!is_null($logon_form)) {
        return $logon_form;
      }*/

      $regADog = new Reg_A_Dog();

      $html = "";
      $html .= $regADog->getADogsInfo();
      return $html;
    }

    function processSelectedTeams() {
      
    }

    function getADogsInfo() {
      $retHTML = "";

      // If we have a dog in the get args, then write to the db
      $retHTML .= $this->writeToDB();

      // else, if we have a team name, ask for the dog details
      if ($_SERVER["REQUEST_METHOD"] == "GET") {
        if (array_key_exists(TEAM_ARGS, $_GET)) {
          $retHTML .= $this->makeOpeningHTML();
          $retHTML .= $this->makeListItemHTML(['reg-a-dog']);
          $retHTML .= $this->makeClosingHTML();
        } else {
          // else, ask for the team
          try {
            $retHTML .= (new TRSE())->get('reg-a-dog');
            if (is_null($retHTML)) {
              return "Unable to get any teams for this musher.";
            }

            $retHTML .= '</select><br><br><button type="submit" value="' . 
              TEAM_NAME_ID . 
              '">Select</form>';
          } catch (Race_Registration_Exception $e) {
            return "<strong>{$e->getMessage()}</strong>";
          }

          return $retHTML;
        }
      }
      
      return $retHTML;
    }

    function makeOpeningHTML() {
      $dogName = DogDefs::NAME;
      $dogAge = DogDefs::AGE;
      $dogForm = DogDefs::FORM_ID;

      $teams_selections_html = <<<GET_DOGS
        <h3>Please provide the details of this dog.</h3>
        <form method="POST" id="{$dogForm}" action="">
          <label for="{$dogName}">Name:</label>
          <input type="text" id="{$dogName}" name="{$dogName}"><br>
          <label for="{$dogAge}">Age:</label>
          <input type="text" id="{$dogAge}" name="{$dogAge}"><br>
          <input type="submit" value="Go">
        </form>
        GET_DOGS;

      return $teams_selections_html;

    }

    function makeListItemHTML(array $params) {

    }
    
    function makeClosingHTML() {

    }

    function writeToDB() {
      // define variables and set to empty values
      $dogname = "";

      $numberError = FALSE;

      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        try{
          if (!array_key_exists(DogDefs::NAME, $_POST)) {
            return null;
          }

          if (!array_key_exists(DogDefs::AGE, $_POST)) {
            return null;
          }

          $dogname = \sanitize_text_field($_POST[DogDefs::NAME]);
          if (empty($dogname)) {
            return ($dogname . "is not a valid name.");
          }

          $dogage = test_input($_POST[DogDefs::AGE]);
          if (empty($dogage)) {
            return ($dogage . " is not a valid age.");
          }
        } catch (\Exception $e) {
          return "Invalid param passed in reg-a-dog_bad_param";
        }

        $teamId = 0;
        $teamNameId = 0;

        $html = TRSE::decodeUnsafeTeamArgs($teamId, $teamNameId);
        if (!is_null($html)) {
          return $html;
        }

        //$dogOwnerFirstName = sanitize_text_field($_POST[DogDefs::OWNER_FIRST_NAME]);
        //$dogOwnerLastName = sanitize_text_field($_POST[DogDefs::OWNER_LAST_NAME]);
        //$dogOwnerEmail = sanitize_test_field($_POST[DogDefs::OWNER_EMAIL]);
        //$dogOwnerUserName = sanitize_test_field($_POST[DogDefs::OWNER_USER_NAME]);
        //$dogOwnerId = test_input($_POST[DogDefs::OWNER_PERSON_ID]);a

        // call wp_get_current_user in form code

        // Get the person id from the owner name
        // get_users?
        // https://rudrastyh.com/wordpress/get-user-id.html
        /*if (null == $dogOwnerId) {
          $wp_current_user = wp_get_current_user();

          if (!$wp_current_user->exists()) {
            return "<p>Internal error registering a dog-1. Please file a bug or contact support.</p>";
          }

          $dogOwnerId = $wp_current_user->ID;
        } else {
          $dogOwnerId = email_exists($dogOwner);
          if (false == $dogOwnerId) {
            $dogOwnerId = username_exists($dogOwner);
            if (false == $dogOwnerId) {
              return "$dogOwner is not a username nor email";
            }
          }
        }*/

        $db;
        $wpUserId = \wp_get_current_user()->ID;

        try {
          $db = new Mush_DB();
        }
        catch(\PDOException $e) {
          return Strings::CONTACT_SUPPORT . Strings::ERROR . 'reg-a-dog_connect.';
        }

        $personId = $db->execAndReturnInt(
          "CALL sp_getPersonIdFromWPUserId(:wpUserId)",
          [$wpUserId],
          "Error getting user information, error reg-a-dog_person-1.");

        $dogId = $db->execAndReturnInt("CALL sp_NewDog (:dogName, :dogAge, :dogOwnerId)",
          ['dogName' => $dogname, 'dogAge' => $dogage, 'dogOwnerId' => $personId],
          "An error ocured saving the dogs information, error reg-a-dog_dog-1");

        try {
          $db->execSql("CALL sp_addDogToTeam(:dogId, :teamId)", 
            ['dogId' => $dogId, 'teamId' => $teamId]);
        } catch (Mush_DB_Exception $e) {
          return "Error reg-a-dog_team-1 occured adding '{$dogname}' to the team.";
        }

        return $dogname . " is now on the team.<br>";
      }

      return null;
    }
  }
?>