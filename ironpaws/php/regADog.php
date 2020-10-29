<?php
// define variables and set to empty values
$bibnumber = $age = 0;
$dogname = "";

$numberError = FALSE;

//$servername = "supermooseapps.com";
$servername = "localhost";
$user= "bryany_mushuser";
//$password = '!\\7JY3}K{dWn#M-+';
$password = 'BeebleBrox9!';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $bibnumber = test_number($_POST["bibNumber"]);
  $dogname = test_input($_POST["dogName"]);
  $dogage = test_number($_POST["dogAge"]);
  $dogowner = test_input($_POST["dogOwner"]);

  try {
    echo "Connecting to musher database...please wait" . "<br>";
    $conn = new PDO("mysql:host=$servername;dbname=bryany_mush", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("INSERT INTO dogpool (name, age) VALUES ('$dogname', $dogage)");
  }
  catch(PDOException $e) {
    die ( "Mush database error. The details are: " . $e->getMessage());
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