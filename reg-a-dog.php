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
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (array_key_exists(TEAM_NAME_ID)) {
          $retHTML .= $this->makeOpeningHTML();
          $retHTML .= $this->makeListItemHTML(['reg-a-dog']);
          $retHTML .= $this->makeClosingHTML();
        } else {
          // else, ask for the team
          $trse = (new TRSE())->get('reg-a-dog');
        }
      }
      
      return $retHTML;
    }

    function makeOpeningHTML() {
      $dogName = DogDefs::NAME;
      $dogAge = DogDefs::AGE;
      $dogForm = DogDefs::FORM_ID;

      $teams_selections_html = <<<GET_DOGS
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

      var_debug($_POST);

      $numberError = FALSE;

      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!array_key_exists($_POST[DogDefs::NAME])) {
          return null;
        }

        if (!array_key_exists($_POST[DogDefs::AGE])) {
          return null;
        }

        $dogname = sanitize_text_field($_POST[DogDefs::NAME]);
        if (empty($dogname)) {
          return ($dogname . "is not a valid name.");
        }

        $dogage = test_input($_POST[DogDefs::AGE]);
        if (empty($dogage)) {
          return ($dogage . " is not a valid age.");
        }

        //$dogOwnerFirstName = sanitize_text_field($_POST[DogDefs::OWNER_FIRST_NAME]);
        //$dogOwnerLastName = sanitize_text_field($_POST[DogDefs::OWNER_LAST_NAME]);
        //$dogOwnerEmail = sanitize_test_field($_POST[DogDefs::OWNER_EMAIL]);
        //$dogOwnerUserName = sanitize_test_field($_POST[DogDefs::OWNER_USER_NAME]);
        //$dogOwnerId = test_input($_POST[DogDefs::OWNER_PERSON_ID]);

        // call wp_get_current_user in form code

        // Get the person id from the owner name
        // get_users?
        // https://rudrastyh.com/wordpress/get-user-id.html
        if (null == $dogOwnerId) {
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
        }

        $db;

        try {
          $db = new Mush_DB();
        }
        catch(\PDOException $e) {
          return Strings::CONTACT_SUPPORT . Strings::ERROR . 'reg-a-dog_connect.';
        }

        $dogId = $db->execAndReturnInt("CALL sp_NewDog (:dogName, :dogAge, :dogOwnerId)",
          ['dogName' => $dogname, 'dogAge' => $dogage, 'owner' => $dogOwnerId]);

          return $dogname . " is sucessfully registered.<br>";
      }

      return null;
    }
  }
?>