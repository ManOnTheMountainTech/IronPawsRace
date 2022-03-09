<?php
/**
 * Plugin Name: Iron Paws
 * Plugin URI: https://supermooseapps.com
 * Description: This extends WordPress for dog mushing.
 * Author: Bryan Young
 * Author URI: https://supermooseapps.com
 * Text Domain: ironpaws
 * Domain Path: /languages
 * Version: 0.3.5
 */

/* Place custom code below this line. */
namespace IronPawsLLC;

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path(__FILE__) . 'tests/test-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'race-results.php';
require_once plugin_dir_path(__FILE__) . 'wp-hooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/add-a-team.php';
require_once plugin_dir_path(__FILE__) . 'reg-a-team.php';
require_once plugin_dir_path(__FILE__) . 'reg-a-dog.php';
require_once plugin_dir_path(__FILE__) . 'wc-payment-complete.php';
require_once plugin_dir_path(__FILE__) . 'wc-payment-hooks.php';
require_once plugin_dir_path(__FILE__) . 'wc-checkout-hooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
//require_once 'includes/autoloader.php';

register_wp_hooks();
register_shortcodes();
register_wc_hooks();
set_exception_handler('IronPawsLLC\def_exception_handler');

function def_exception_handler(\Throwable $er) {
    $instance = User_Visible_Exception_Thrower::getInstance($er);
    if (!is_null($instance)) {
        $instance = ", instance=$instance";
    }
    _e("Oh no! The harness broke! code={$er->getCode()}{$instance}<br>");
    echo User_Visible_Exception_Thrower::getUserMessage($er);    
}

function register_shortcodes() {
    add_shortcode('ironpaws_create_team_race_stage_entry', 'IronPawsLLC\\do_shortcode_create_team_race_stage_entry');
    add_shortcode('ironpaws_register_a_team', 'IronPawsLLC\\do_shortcode_reg_a_team');  
    add_shortcode('ironpaws_fetch_teams', ['IronPawsLLC\\Fetch_Teams', 'do_shortcode']);
    add_shortcode('ironpaws_create_trse', ['IronPawsLLC\\TRSE', 'do_shortcode']);
    add_shortcode('ironpaws_add_a_team', 'IronPawsLLC\\do_shortcode_add_a_team');
    add_shortcode('ironpaws_run_tests', 'IronPawsLLC\\do_shortcode_run_tests');
    add_shortcode('ironpaws_race_stage_entry', ['IronPawsLLC\\Race_Stage_Entry', 'do_shortcode']);
    add_shortcode('ironpaws_test_updateTRSEForRSE', 'IronPawsLLC\\do_shortcode_test_sp_updateTRSEForRSE');
    add_shortcode('ironpaws_race_results', ['IronPawsLLC\\Race_Results', 'do_shortcode']);
    add_shortcode('ironpaws_reg_a_dog', ['IronPawsLLC\\Reg_A_Dog', 'do_shortcode']);
}

// wp-hooks.php
function register_wp_hooks() {
    //register_activation_hook(__FILE__, ['IronPawsLLC\\WP_Hooks', 'install']);
    add_action('user_register', ['IronPawsLLC\\WP_Hooks', 'user_register']);
    add_action('delete_user',   ['IronPawsLLC\\WP_Hooks', 'delete_user']);
    add_action('delete_user_form', 'IronPawsLLC\\ironpaws_wp_delete_user_form');

    // https://stackoverflow.com/questions/5034826/wp-nav-menu-change-sub-menu-class-name
    add_filter('wp_nav_menu_items', 'IronPawsLLC\\ironpaws_add_loginout_link', 10, 2);
    add_action('wp_enqueue_scripts', 'IronPawsLLC\\ironpaws_wp_load_css' );

    // https://stackoverflow.com/questions/38693992/notice-ob-end-flush-failed-to-send-buffer-of-zlib-output-compression-1-in
    /**
     * Proper ob_end_flush() for all levels
     *
     * This replaces the WordPress `wp_ob_end_flush_all()` function
     * with a replacement that doesn't cause PHP notices.
     */
    remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
    add_action( 'shutdown', function() {
        while ( @ob_end_flush() );
    } );
    add_filter('load_textdomain_mofile', ['IronPawsLLC\\WP_Hooks', 'load_my_own_textdomain'], 10, 2 );
    add_action('init', ['IronPawsLLC\\WP_Hooks', 'init'] );
    //add_action('wp_logout',  ['IronPawsLLC\\WP_Hooks', 'login']);
    //add_action('wp_login',  ['IronPawsLLC\\WP_Hooks', 'logout']);
    add_action('register_form',  ['IronPawsLLC\\WP_Hooks', 'registration_form']);
}

// In: wc-payment-complete.php
function register_wc_hooks() {
/* This is the one that is hit when everything is said and done */
    add_action( 'woocommerce_payment_complete', 'IronPawsLLC\\ironpaws_woocommerce_payment_complete');
    add_action( 'woocommerce_order_details_after_customer_details', 'IronPawsLLC\\ironpaws_order_details_after_customer_details');
    add_action( 'woocommerce_register_form', ['IronPawsLLC\\WP_Hooks', 'registration_form']);

    // @see woocommerce_form_field(...) in wc-template-functions.php
    add_action( 'woocommerce_after_checkout_registration_form', ['IronPawsLLC\\WC_Checkout_Hooks', 'after_checkout_registration_form']);
    //add_action( 'woocommerce_checkout_process', ['IronPawsLLC\\WC_Checkout_Hooks', 'checkout_process']);
}

/* Place custom code above this line. */
?>
