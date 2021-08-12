<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once(plugin_dir_path(__FILE__) . 'tests/users.php');
    $users = Users::get(Users::KEY_FIRST_NAME, "Bryan");
    $users = Users::get(Users::KEY_FIRST_NAME, "StarBurst");
?>