<?php
  defined( 'ABSPATH' ) || exit;

  namespace IronPawsLLC;

  require_once('setIncPath.php');
  require_once('globals.php');
  require_once(NON_WEB_PHP . 'mush-db.php');

  $numberError = FALSE;

  if(!session_id()) session_start();

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $address= test_input($_POST["address"]);
    if (empty($address)) {
      die ($address . "Is not a valid address.");
    }

    $po_box = test_input($_POST["po-box"]);
    $city = test_input($_POST["city"]);
    if (empty($city)) {
      die ($city . "Is not a valid city.");
    }

    $state = test_input($_POST["stateIsoCode"]);
    $postal = test_input($_POST["postal"]);
    $district = test_input($_POST["district"]);
    $neighborhood = test_input($_POST["neighborhood"]);
    $suplemental = test_input($_POST["supplemental"]);
    $department = test_input($_POST["department"]);
    $building_name = test_input($_POST["building-name"]);
    $type = test_input($_POST["addr-type"]);

    $execSql = "CALL NewAddress ('{$address}', '{$po_box}', '{$city}', '{$state}', '{$district}', '{$neighborhood}', '{$postal}', '{$_SESSION[COUNTRY]}', '{$suplemental}', '{$department}', '{$building_name}', '{$type}')";
    echo "sql to execute: " . $execSql . "<BR>";

    try {
      Mush_DB::connect()->exec($execSql);
    }
    catch(\PDOException $e) {
      die ( "The database returned an error while registering an address. The details are: " . $e->getMessage());
    }
  }

?>