<?php
// define variables and set to empty values
$bibnumber = $age = 0;
$dogname = "";

$numberError = FALSE;

$servername = "supermooseapps.com";
$user= "bryany_mushuser";
$password = '!\\7JY3}K{dWn#M-+';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $bibnumber = test_number($_POST["bibNumber"]);
  $dogname = test_input($_POST["dogName"]);
  $age = test_number($_POST["age"]);

  echo $bibnumber . "<br>";
  echo $dogname . "<br>";
  echo $age . "<br>";

  try {
    echo "Connecting to musher database...please wait" . "<br>";
    $conn = new PDO("mysql:host=$servername;dbname=bryany_mush", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  catch(PDOException $e) {
    echo "Connection to musher database failed: " . $e->getMessage();
  }
}

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