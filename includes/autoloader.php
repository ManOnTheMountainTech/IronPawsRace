<?php
    defined( 'ABSPATH' ) || exit;

    spl_autoload_register(function ($class_name) {
        ifMatchingNamespaceLoad($class_name, 'IronPaws', '');
        ifMatchingNamespaceLoad($class_name, 'Algorithms', 'algorithms/');
    });

    function ifMatchingNamespaceLoad(string $class_name, $namespace, string $pathFromInclude) {
        $split_class_name = explode("{$namespace}\\", $class_name);

        if (isset($split_class_name[1])) {
            $pieces = explode('_', $split_class_name[1]);
            $proto_filename = implode('-', $pieces);
            require_once $pathFromInclude . strtolower($proto_filename) . '.php';
        }
    }
?>