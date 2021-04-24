<?php
/**
 * Plugin Name: Iron Paws
 * Plugin URI: https://supermooseapps.com
 * Description: This extends WordPress for dog mushing.
 * Author: Bryan Young
 * Author URI: https://supermooseapps.com
 * Version: 0.1.2
 */

/* Place custom code below this line. */
require_once('reg-a-team.php');
require_once('fetch-teams.php');
require_once('woo-connect.php');
require_once('add-a-team.php');
require_once(plugin_dir_path(__FILE__) . 'tests/wc-customers.php');

defined( 'ABSPATH' ) || exit;

register_shortcodes();

function register_shortcodes() {
    add_shortcode('ironpaws_register_a_team', 'do_shortcode_reg_a_team');  
    add_shortcode('ironpaws_fetch_teams', 'do_shortcode_fetch_teams');
    add_shortcode('ironpaws_add_a_team', 'do_shortcode_add_a_team');
    add_shortcode('ironpaws_write_team_to_db', 'do_shortcode_write_team_to_db');
    add_shortcode('ironpaws_run_tests', 'do_shortcode_run_tests');
}

/*
add_action( 'rest_api_init', function () {
    register_rest_route( 'ironpaws/v1', '/woocommerce', array(
      'methods'  => 'POST',
      'callback' => 'init',
    ) );
} );

function init($request) {
    echo 'IronPaws initd';
}
*/
//add_action( 'woocommerce_order_status_completed', 'ironpaws_woocommerce_order_status_completed');
//add_action( 'woocommerce_payment_complete_order_status_completed', 'ironpaws_woocommerce_payment_complete_order_status');

/* This is the one that is hit when everything is said and done */
add_action( 'woocommerce_payment_complete', 'ironpaws_woocommerce_payment_complete');



/* Place custom code above this line. */
?>