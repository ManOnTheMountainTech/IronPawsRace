<?php
    defined( 'ABSPATH' ) || exit;

    namespace IronPawsLLC;

require_once('setIncPath.php');
require_once('globals.php');
require_once(NON_WEB_PHP . 'mush-db.php');
require_once(NON_WEB_PHP . 'io.php');

if(!session_id()) session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $country= test_number($_POST["country"]);
  if (empty($country)) {
    die ($country . " is not a valid country.");
  }

  $_SESSION['country'] = $country;

  $language = test_input($_POST["language"]);
  if (empty($language)) {
    die ($language . " is not a valid iso-639-1 language code.");
  }
}

echo_header_body_footer('navbar.html', 'addressForm.html', 'footer.html');

//header("Location: /addressForm.html");
?>