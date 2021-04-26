<?php
    defined( 'ABSPATH' ) || exit;
    
    function val_or_zero_int(INT $var) {
        return (isset($var) ? ($var) : 0);
    }

    function val_or_zero_array($var_name, array $array) {
        return array_key_exists($var_name, $array) ? $array[$var_name] : 0;
    }

    function makeHTMLErrorMessage($error_msg) {
        return "<p>$error_msg</p>";
    }
?>