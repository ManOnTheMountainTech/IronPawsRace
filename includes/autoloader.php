<?php
    defined( 'ABSPATH' ) || exit;

    const NAMESPACE_PAIRS = ["IronPawsLLC"=>"", "IronPawsLLC\\Mush"=>"..", "IronPawsLLC\\Mush\Lib"=>""];

    spl_autoload_register(function ($class_name) {
        ifMatchingNamespaceLoad($class_name, 'IronPawsLLC', '');
        ifMatchingNamespaceLoad($class_name, 'Algorithms', 'algorithms/');
    });

    // Need to match precisely for sub namespaces

    function ifMatchingNamespaceLoad(string $class_name, $namespace, string $pathFromInclude) {
        $split_class_name = explode("{$namespace}\\", $class_name);

        if (isset($split_class_name[1])) {
            $pieces = explode('_', $split_class_name[1]);
            $proto_filename = implode('-', $pieces);
            require_once $pathFromInclude . strtolower($proto_filename) . '.php';
        }
    }
?>