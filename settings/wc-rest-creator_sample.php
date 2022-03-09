<?php
    namespace IronPawsLLC;

    defined( 'ABSPATH' ) || exit;

    require_once plugin_dir_path(__FILE__) . '../includes/wp-defs.php';
    require_once plugin_dir_path(__FILE__) . '../includes/debug.php';
    require plugin_dir_path(__FILE__) . '../vendor/autoload.php';

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;

    // https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-rest-authentication.htm
    class WC_Rest_Creator {  
          static function create_wc() {
            return new Client(
                '<URL>', 
                'ck_<key>', 
                'cs_<key>',
                [
                    'wp_api' => true,
                    'version' => 'wc/v3'
                ]
            );
        }

    
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