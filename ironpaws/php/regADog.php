<?php
// define variables and set to empty values
$bibnumber = $age = 0;
$dogname = "";
$dogbirthday = "";

$numberError = FALSE;

$servername = "supermooseapps.com";
//$servername = "localhost";
$user= "bryany_mushuser";
$password = '9E{y)E;32.Qep7%m';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  //$bibnumber = test_number($_POST["bibNumber"]);
  $dogname = test_input($_POST["dogName"]);
  if (empty($dogname)) {
    die ($dogname . "is not a valid name.");
  }

  $dogbirthday = test_input($_POST["dogDOB"]);
  if (empty($dogbirthday)) {
    die ($dogbirthday . " is not a valid birth date.");
  }

  //$dogowner = test_input($_POST["dogOwner"]);

  $execSql = "CALL NewDog ('{$dogname}', '{$dogbirthday}')";
  echo "sql to execute: " . $execSql . "<BR>";

  try {
    echo "Connecting to musher database...please wait<br>";
    $conn = new PDO("mysql:host=$servername;dbname=bryany_mush", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec($execSql);
  }
  catch(PDOException $e) {
    die ( "The database returned an error while registering a dog. The details are: " . $e->getMessage());
  }
}

echo $dogname . " is sucessfully registered.<br>";

// Returns: 0 if the number is invalid
function test_number($number) {
    if (is_numeric(test_input($number))) { 
      return $number; }
    else {
      $number = 0; }
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>