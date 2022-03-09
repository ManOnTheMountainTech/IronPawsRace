<?php

    namespace IronPawsLLC;

    defined( 'ABSPATH' ) || exit;

    // Returns: 0 if the number is invalid
    function test_number($number) {
        if (is_numeric(test_input($number))) { 
            return $number; }
        else {
            $number = 0; }
    }

    // Generic all-date validation function.
    // WARNING: Gaurd this with an exception handler. Trim can explode with
    // bad input.
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
    return $data;
    }
?>