<?php
  namespace IronPaws;

  defined( 'ABSPATH' ) || exit;

  require_once plugin_dir_path(__FILE__) . 'includes/strings.php';
  //require_once plugin_dir_path(__FILE__) . 'includes/dogdefs.php';
  require_once plugin_dir_path(__FILE__) . 'includes/autoloader.php';
  require_once plugin_dir_path(__FILE__) . 'includes/verify.php';

  class Reg_A_Dog {
    const REG_A_DOG_NONCE_ACTION = 'reg-dog-nonce-action';
    const REG_A_DOG_NONCE_NAME = 'reg-dog-nonce-name';

    static function do_shortcode() {
      $logon_form = ensure_loggedon();
      if (!is_null($logon_form)) {
        return $logon_form;
      }

      Strings::init();
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
        if (array_key_exists(TEAM_NAME_ID, $_GET)) {
          $retHTML .= $this->makeOpeningHTML();
          $retHTML .= $this->makeListItemHTML(['']);
          $retHTML .= $this->makeClosingHTML();
        } else {
          // else, ask for the team
          try {
            $retHTML .= (new TRSE())->get('');
            if (is_null($retHTML)) {
              return __("Unable to get any teams for this musher.", "ironpaws");
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

    function makeOpeningHTML(?array $params = null) {
      $dogName = DogDefs::NAME;
      $dogAge = DogDefs::AGE;
      $dogForm = DogDefs::FORM_ID;

      $nonce = wp_nonce_field(self::REG_A_DOG_NONCE_ACTION, self::REG_A_DOG_NONCE_NAME);

      $teams_selections_html = <<<GET_DOGS
        <h3>Please provide the details of this dog.</h3>
        <form method="POST" id="{$dogForm}" action="">
          {$nonce}
          <label for="{$dogName}">Name:</label>
          <input type="text" id="{$dogName}" name="{$dogName}"><br>
          <label for="{$dogAge}">Age:</label>
          <input type="text" id="{$dogAge}" name="{$dogAge}"><br>
          <input type="submit" value="Go">
        </form>
        GET_DOGS;

      return $teams_selections_html;

    }

    function makeListItemHTML(?array $params = null) {

    }
    
    function makeClosingHTML(?array $params = null) {

    }

    // In POST:
    // @param- self::REGA_DOG_NONCE_NAME
    // @param- DogDefs::NAME
    // @param- DogDefs::AGE
    // @param- DogDefs::NAME
    function writeToDB() {
      // define variables and set to empty values
      $dogname = "";

      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        try{
          if (array_key_exists(self::REG_A_DOG_NONCE_NAME, $_POST)) {
            if (!wp_verify_nonce($_POST[self::REG_A_DOG_NONCE_NAME], self::REG_A_DOG_NONCE_ACTION)) {
              throw new \Exception();
            }
          } else {
            throw new \Exception();
          }

          if (!array_key_exists(DogDefs::NAME, $_POST)) {
            return DogDefs::NAME . __(" is not present.");
          }

          if (!array_key_exists(DogDefs::AGE, $_POST)) {
            return DogDefs::AGE . __(" is not present.");
          }

          $dogname = \sanitize_text_field($_POST[DogDefs::NAME]);
          if (empty($dogname)) {
            return ($dogname . __(" is not a valid name."));
          }

          $dogage = test_input($_POST[DogDefs::AGE]);
          if (empty($dogage)) {
            return ($dogage . __(" is not a valid age."));
          }
        } catch (\Exception $e) {
          return Strings::get_bad_arguments_msg();
        }

        $teamId = 0;

        $html = TRSE::decodeUnsafeTeamNameId($teamId);
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

        $wpUserId = \get_current_user_id();

        try {
          $db = new Mush_DB();
        }
        catch(\PDOException $e) {
          return Strings::$CONTACT_SUPPORT . Strings::$ERROR . 'reg-a-dog_connect.';
        }

        try {
        $personId = $db->execAndReturnInt(
          "CALL sp_getPersonIdFromWPUserId(:wpUserId)",
          [$wpUserId],
          "Error getting user information, error reg-a-dog_person-1.");

        $db->execAndReturnInt("CALL sp_addNewDogToTeam (:dogName, :dogAge, :dogOwnerId, :teamId)",
          ['dogName' => $dogname, 'dogAge' => $dogage, 'dogOwnerId' => $personId, 'teamId' => $teamId],
          "An error ocured saving the dogs information, error reg-a-dog_dog-1");
        } catch (\Exception $e) {
          return User_Visible_Exception_Thrower::throwErrorCoreException(__("Error in adding a dog to a team.", 0, $e));
        }

        $dogname .= " is now on the team.<br>" . Strings::$NEXT_STEPS . "<br>";

        return $dogname;
      }

      return null;
    }
  }
?>