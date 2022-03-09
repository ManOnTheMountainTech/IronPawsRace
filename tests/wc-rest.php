<?php
    namespace IronPawsLLC\Test;

use IronPawsLLC\WC_Rest_Creator;

    defined( 'ABSPATH' ) || exit;

    require_once '../settings/wc-rest-creater.php';

    function console_log($message) {
        $STDERR = fopen("php://stderr", "w");
                  fwrite($STDERR, "\n".$message."\n\n");
                  fclose($STDERR);
    }

    console_log("Hello");
    $wc_rest_client = new WC_Rest_Creator("wc/v3");
    echo $wc_rest_client;
    var_dump($wc_rest_client);
?>