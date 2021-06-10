<?php
/**
 * Plugin Name: Iron Paws
 * Plugin URI: https://supermooseapps.com
 * Description: This extends WordPress for dog mushing.
 * Author: Bryan Young
 * Author URI: https://supermooseapps.com
 * Version: 0.1.6
 */

/* Place custom code below this line. */
defined( 'ABSPATH' ) || exit;

namespace IronPaws;

require_once 'autoloader.php';
require_once plugin_dir_path(__FILE__) . 'tests/test-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'ironpaws-wp-hooks.php';
require_once plugin_dir_path(__FILE__) . 'add-a-team.php';
require_once plugin_dir_path(__FILE__) . 'reg-a-team.php';
require_once plugin_dir_path(__FILE__) . 'ironpaws-payment-complete.php';
require_once plugin_dir_path(__FILE__) . 'wc-payment-hooks.php';

register_wp_hooks();
register_shortcodes();
register_wc_hooks();

function register_shortcodes() {
    add_shortcode('ironpaws_create_team_race_stage_entry', 'IronPaws\\do_shortcode_create_team_race_stage_entry');
    add_shortcode('ironpaws_register_a_team', 'IronPaws\\do_shortcode_reg_a_team');  
    add_shortcode('ironpaws_fetch_teams', ['IronPaws\\Fetch_Teams', 'do_shortcode']);
    add_shortcode('ironpaws_create_trse', ['IronPaws\\TRSE', 'do_shortcode']);
    add_shortcode('ironpaws_add_a_team', 'IronPaws\\do_shortcode_add_a_team');
    add_shortcode('ironpaws_run_tests', 'IronPaws\\do_shortcode_run_tests');
    add_shortcode('ironpaws_race_stage_entry', ['IronPaws\\Race_Stage_Entry', 'do_shortcode']);
}

function register_wp_hooks() {
    add_action('user_register', 'IronPaws\\ironpaws_user_register');
    add_action('delete_user', 'IronPaws\\ironpaws_wp_delete_user');
    add_action('delete_user_form', 'IronPaws\\ironpaws_wp_delete_user_form');
    add_filter('wp_nav_menu_items', 'IronPaws\\ironpaws_add_loginout_link', 10, 2);
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

function register_wc_hooks() {
/* This is the one that is hit when everything is said and done */
    add_action( 'woocommerce_payment_complete', 'IronPaws\\ironpaws_woocommerce_payment_complete');
    add_action( 'woocommerce_order_details_after_customer_details', 'IronPaws\\ironpaws_order_details_after_customer_details');
}

/* Place custom code above this line. */
?>