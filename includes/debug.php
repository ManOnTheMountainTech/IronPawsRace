<?php
    namespace IronPaws;
    
    defined( 'ABSPATH' ) || exit;

    static $error_instance = 0;
    const MEASURE_PERF = true;

    function is_wp_debug() {
        return (defined('WP_DEBUG') && true === WP_DEBUG);
    }

    function var_debug($var) {
        if (is_wp_debug()) {
            \var_dump($var);
        }
    }

    if (!function_exists('write_log')) {
        function write_log($message, $log = "") {
            if (\is_array($log) || \is_object($log)) {
                \error_log($message . print_r($log, true));
            } else {
                \error_log($message . $log);
            }
        }
    }

    if (!function_exists('echo_r')) {
        function echo_r($message, $log = "") {
            if (\is_array($log) || \is_object($log)) {
                \error_log($message . print_r($log, true));
            } else {
                \error_log($message . $log);
            }
        }
    }

    function statement_log($function, $line, $message, $log = "") {
        if (\is_array($log) || \is_object($log)) {
            $log = \print_r($log, true);
        }

        \error_log(\sprintf("%s:%s %s:%s", $function, $line, $message, $log));
    }

    function object_to_html($log) {
        if (\is_array($log) || \is_object($log)) {
            \error_log(pp($log));
        } else {
            \error_log(pp($log));
        }
    }

    function html_walker($arg): string {
        foreach ($arr as $key=>$val){
            if (\is_array($val)  || \is_object($val)) {
                $retStr .= '<li>' . $key . ' => ' . pp($val) . '</li>';
            }else{
                $retStr .= '<li>' . $key . ' => ' . $val . '</li>';
            }
        }

        return $retStr;
    }

    function var_log($arg) {
        if (empty($arg)) {
            return;
        }

        ob_start();
        var_dump($arg);
        $dumped = ob_get_contents();
        error_log($dumped);
        ob_end_flush();
    }

    // Pretty-print. Doesn't work.
    function pp($arg){
        $retStr = '<ul>';

        if (\is_object($arg)) {
            $retStr = html_walker((array)$arg);
        } else if (is_array) {
            $arr = html_walker($arg);
        }

        $retStr .= '</ul>';
        return $retStr;
    }

    function pre_print($obj) {
        return '<pre>' . print_r($obj, true) . '</pre>';
    }

    function dump_last_request($woocommerce) {
        $lastRequest = $woocommerce->http->getRequest();
        write_log('lastRequest');
        write_log( ' url: ', $lastRequest->getUrl()); // Requested URL (string).
        write_log( ' method: ', $lastRequest->getMethod());  // Request method (string).
        write_log( ' parameters: ', $lastRequest->getParameters()); // Request parameters (array).
        write_log( ' Headers:', $lastRequest->getHeaders()); // Request headers (array).
        write_log( ' Body:', $lastRequest->getBody()); // Request body (JSON).

        // Last response data.
        write_log('lastResponse');
        $lastResponse = $woocommerce->http->getResponse();
        write_log(' code: ', $lastResponse->getCode()); // Response code (int).
        write_log(' headers: ', $lastResponse->getHeaders()); // Response headers (array).
        write_log(' body: ', $lastResponse->getBody()); // Response body (JSON).
    }

    function print_if_set($var, $name_of_var) {
        echo $name_of_var . "= ";
        \print_r(isset($var) ? ($var) : "not set");
    }

    // echo's to the wp log if the array's key index is defined
    // @param : array: The array containing the key
    // @param : index: The index of the array to validate
    // @param : name_of_array: The array's symbolic representation
    function print_if_key_set(array $array, $index, $name_of_array) {
        echo $name_of_array . '= ';
        if (array_key_exists($index, $array)) {
            \print_r($array);
        } else {
            echo 'not set';
        }
    }

    // log's to the wp log if the array's key index is defined
    // @param : array: The array containing the key
    // @param : index: The index of the array to validate
    // @param : name_of_array: The array's symbolic representation
    function log_if_key_set(array $array, $index, $name_of_array) {
        write_log($name_of_array . '= ', (array_key_exists($index, $array))
             ? \print_r($array, true) : 'not set' );
    }
?>