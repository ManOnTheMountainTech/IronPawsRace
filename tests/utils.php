<?php
    namespace IronPawsLLC\Test;

    defined( 'ABSPATH' ) || exit;

    require_once '..\includes\users.php';

    function console_log($message) {
        $STDERR = fopen("php://stderr", "w");
                  fwrite($STDERR, "\n".$message."\n\n");
                  fclose($STDERR);
    }

    function hoursMinutesSecondsToSecondsF() {
        $millis = hoursMinutesSecondsToSecondsF(6,12,18);
        
        console_log("time = {$millis}");
    }
    
      function secondsFToHMS() {
          $timeString = secondsFToHMS(3601.25);

          console_log($timeString);
      }
?>