<?php
  namespace IronPaws;

  defined( 'ABSPATH' ) || exit;

  require_once("setIncPath.php");
  require_once(non_web_php . "/mush-db.php");

  class Reg_A_Dog {

    static function do_shortcode() {
      $dogRegistration = new Reg_A_Dog();
      $retHTML = "";
      $retHTML .= $dogRegistration->writeToDB();
    } 

    function writeToDB() {
      // define variables and set to empty values
      $dogname = "";

      $numberError = FALSE;

      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $dogname = sanitize_text_field($_POST["dogName"]);
        if (empty($dogname)) {
          return ($dogname . "is not a valid name.");
        }

        $dogage = test_input($_POST["dogAge"]);
        if (empty($dogage)) {
          return ($dogage . " is not a valid age.");
        }

        $dogOwnerFirstName = sanitize_text_field($_POST["dogOwnerFirstName"]);
        $dogOwnerLastName = sanitize_text_field($_POST["dogOwnerLastName"]);
        $dogOwnerEmail = sanitize_test_field($_POST["dogOwnerEmail"]);
        $dogOwnerUserName = sanitize_test_field($_POST["dogOwnerUserName"]);
        $dogOwnerId = test_input($_POST["dogOwnerId"]);

        // call wp_get_current_user in form code
        $dogOwnerId;

        // Get the person id from the owner name
        // get_users?
        // https://rudrastyh.com/wordpress/get-user-id.html
        if (null != $dogOwnerId) {
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

        $db = new MushDB();
        $dogId = $db->execAndReturnInt("CALL sp_NewDog (:dogName, :dogAge, :dogOwnerId)",
          ['dogName' => $dogname, 'dogAge' => $dogage, 'owner' => $dogOwnerId]);
      }

      return $dogname . " is sucessfully registered.<br>";
    }
  }
?>