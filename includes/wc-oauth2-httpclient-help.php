<?php
    namespace IronPawsLLC;

    use Automattic\WooCommerce\HttpClient\HttpClientException;
    
    require plugin_dir_path(__FILE__) . '../vendor/autoload.php';
    require_once "autoloader.php";

    class WC_OAuth2_HttpClient_Help {
       

        // @function processResponse
        // @param $result - result from a call to the WooCommerce REST API
        // @returns : null = failure, associative object = success
        // ----
        // Internally, Client calls HttpClient, which sets the parameters as CURL
        // options, and then calls CURL. The result is processed by json_decode.
        // json_decode can return true, false, or null. However, the body still
        // may contain valid data. Try a little harder to get it.
    }
?>