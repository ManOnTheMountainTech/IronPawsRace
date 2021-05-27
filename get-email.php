<?php
    defined( 'ABSPATH' ) || exit;

    namespace IronPaws;

    require_once(plugin_dir_path(__FILE__) . 'includes/wp-defs.php');
    require_once(plugin_dir_path(__FILE__) . 'includes/debug.php');
    require_once(plugin_dir_path(__FILE__) . "includes/RACE_CLASS_IDes.php");

    function do_shortcode_get_email() {
        $email = EMAIL;

        $add_team_html = <<<GET_EMAIL
        <form method="get" id="email_form" action="get-email">
            <label for="{$email}">Youre email address:</label>
            <input type="text" id="{$email}" name="{$email}"><br>
            <input type="submit" value="Register my team">
        </form>
        GET_EMAIL;
    }
?>