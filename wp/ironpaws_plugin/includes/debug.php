<?php
/*if (!function_exists('write_log')) {

    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}*/
    if (!function_exists('write_log')) {
        function write_log($message, $log = "") {
            if (is_array($log) || is_object($log)) {
                error_log($message . print_r($log, true));
            } else {
                error_log($message . $log);
            }
        }
    }

    function object_to_html($log) {
        if (is_array($log) || is_object($log)) {
            error_log(pp($log));
        } else {
            error_log(pp($log));
        }
    }

    function pp($arr){
        $retStr = '<ul>';
        if (is_array($arr)){
            foreach ($arr as $key=>$val){
                if (is_array($val)  || is_object($val)) {
                    $retStr .= '<li>' . $key . ' => ' . pp($val) . '</li>';
                }else{
                    $retStr .= '<li>' . $key . ' => ' . $val . '</li>';
                }
            }
        }
        $retStr .= '</ul>';
        return $retStr;
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
?>