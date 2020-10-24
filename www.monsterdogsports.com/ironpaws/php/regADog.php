<?php
// define variables and set to empty values
$bibnumber = $age = 0;
$dogname = "";

$numberError = FALSE;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $bibnumber = test_number($_POST["bibNumber"]);
  $dogname = test_input($_POST["dogName"]);
  $age = test_number($_POST["age"]);

  echo $bibnumber . "<br>";
  echo $dogname . "<br>";
  echo $age . "<br>";
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