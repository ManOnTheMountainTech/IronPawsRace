<?php
/**
 * Plugin Name: Iron Paws
 * Plugin URI: https://supermooseapps.com
 * Description: This extends WordPress for dog mushing.
 * Author: Bryan Young
 * Author URI: https://supermooseapps.com
 * Version: 0.1.4
 */

/* Place custom code below this line. */
defined( 'ABSPATH' ) || exit;

require_once('reg-a-team.php');
require_once('fetch-teams.php');
require_once('wc-rest.php');
require_once('add-a-team.php');
require_once('ironpaws-payment-complete.php');
require_once(plugin_dir_path(__FILE__) . 'ironpaws_wp_hooks.php');
require_once(plugin_dir_path(__FILE__) . 'tests/wc-customers.php');

register_wp_hooks();
register_shortcodes();

function register_shortcodes() {
    add_shortcode('ironpaws_register_a_team', 'do_shortcode_reg_a_team');  
    add_shortcode('ironpaws_fetch_teams', ['Teams', 'do_shortcode_fetch_teams']);
    add_shortcode('ironpaws_add_a_team', 'do_shortcode_add_a_team');
    add_shortcode('ironpaws_write_team_to_db', 'do_shortcode_write_team_to_db');
    add_shortcode('ironpaws_run_tests', 'do_shortcode_run_tests');
}

function register_wp_hooks() {
    add_action('user_register', 'ironpaws_wp_insert_user');
    add_action('delete_user', 'ironpaws_wp_delete_user');
    add_action('delete_user_form', 'ironpaws_wp_delete_user_form');
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
add_action( 'woocommerce_order_details_after_customer_details', 'ironpaws_order_details_after_customer_details');

/* Place custom code above this line. */
?>