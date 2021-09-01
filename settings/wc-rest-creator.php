<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . '../includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . '../includes/debug.php';
    require plugin_dir_path(__FILE__) . '../vendor/autoload.php';

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;

    class WC_Rest_Creator {  
          static function create_wc() {
            /*return new Client(
                'https://ironpawsllc.com', 
                'ck_f79eca540f4d74a63f85845426de32283f80f9d0', 
                'cs_056dc40407f6219fbd5705594c32460130175aa9',
                [
                    'wp_api' => true,
                    'version' => 'wc/v3'
                ]
            );*/

            return new Client(
                'https://beta.ironpawsllc.com', 
                'ck_0bb09da903e0b344cb4112b885a077fbc3a501c2', 
                'cs_2fd8415ab167193563eefaea976952a483e6d54e',
                [
                    'wp_api' => true,
                    'version' => 'wc/v3'
                ]
            );

            /*return new Client(
                'http://localhost', 
                'ck_f79eca540f4d74a63f85845426de32283f80f9d0', 
                'cs_056dc40407f6219fbd5705594c32460130175aa9',
                [
                    'wp_api' => true,
                    'version' => 'wc/v3',
                    'query_string_auth' => true,
                    'verify_ssl' => false
                ]
            );*/
        }

        function getResponseBody($response) {
            return $this->$woocommerce->http->getResponse()->getBody();
        }

        // @function processResponse
        // @param $result - result from a call to the WooCommerce REST API
        // @returns : null = failure, associative object = success
        // ----
        // Internally, Client calls HttpClient, which sets the parameters as CURL
        // options, and then calls CURL. The result is processed by json_decode.
        // json_decode can return true, false, or null. However, the body still
        // may contain valid data. Try a little harder to get it.
        static function processResponse($result) {
            if ((false == $result) || (null == $result)) {
                $body = getResponseBody($result);
                if ((false == $body) || (null == $body)) {
                    return null;
                } else {
                    $body = json_decode($body);
                    if ((false == $body) || (null == $body)) {return null;}
                    
                    return $body;
                }
            }

            return $result;
        }

        static function handleHttpClientException(HttpClientException $ehce) {
            write_log(__FUNCTION__ . __LINE__ . "Caught. Message:", $e->getMessage() ); // Error message.
            write_log(" Request:", $e->getRequest() ); // Last request data.
            write_log(" Response:", $e->getResponse() ); // Last response data.
        }
    }

?>