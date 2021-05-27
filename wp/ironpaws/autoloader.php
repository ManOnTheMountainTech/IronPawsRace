<?php
    defined( 'ABSPATH' ) || exit;

    spl_autoload_register(function ($class_name) {
        // Generated from regex101.com
        $split_class_name = explode('IronPaws\\', $class_name);

        if (isset($split_class_name[1])) {
            $pieces = explode('_', $split_class_name[1]);
            $proto_filename = implode('-', $pieces);
            require_once strtolower($proto_filename) . '.php';
        }
    });
?>