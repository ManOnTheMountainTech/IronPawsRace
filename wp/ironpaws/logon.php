<?php
  defined( 'ABSPATH' ) || exit;

  namespace IronPaws;

  // Returns a login form if the user is not logged on
  // @return: if not logged in, the logon form
  //          if logged in, null
  function ensure_loggedon() {
      if (!is_user_logged_in()) {
          // log them in
          if (function_exists('woocommerce_login_form') &&
            function_exists('woocommerce_output_all_notices')) {
              ob_start();
              echo "Welcome to Iron Paws. Please login.";
              woocommerce_output_all_notices();
              woocommerce_login_form();
              return ob_get_clean();
            } else {
              return wp_login_form(array('echo' => false));
            }
      }

      return null;
  }
?>