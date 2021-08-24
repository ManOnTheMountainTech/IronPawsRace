<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once 'users.php';
    $users = Users::get(Users::KEY_FIRST_NAME, "Bryan");
    $users = Users::get(Users::KEY_FIRST_NAME, "StarBurst");
?>