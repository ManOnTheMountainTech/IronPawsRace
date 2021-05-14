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

      // Processes get args to wc customer params
  // @return: The musher name in first and last format
  function makeWCParamsFromFirstLast() {
    if (isset($_GET[FIRST_NAME]) && isset($_GET[LAST_NAME])) {
      $first_name = $_SESSION[FIRST_NAME] = sanitize_text_field($_GET[FIRST_NAME]);
      $last_name = $_SESSION[LAST_NAME] = sanitize_text_field($_GET[LAST_NAME]);

      if (('' == $first_name) || ('' == $last_name)) {
        throw new Exception(FORM_INCOMPLETE_MSG, FORM_INCOMPLETE_ERROR);
      }

      $params = array('first_name' => $first_name, 
      'last_name' => $last_name, 
        'role' => 'all');
    } else {
      throw new Exception(FORM_INCOMPLETE_MSG, FORM_INCOMPLETE_ERROR);
    }

    return $params;
  }
?>