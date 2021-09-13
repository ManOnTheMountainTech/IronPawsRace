<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require plugin_dir_path(__FILE__) . '../vendor/autoload.php';

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;

    class WC_Rest_Creator {  
          static function create_wc() {
            return new Client(
                'https://beta.ironpawsllc.com', 
                'ck_0bb09da903e0b344cb4112b885a077fbc3a501c2', 
                'cs_2fd8415ab167193563eefaea976952a483e6d54e',
                [
                    'wp_api' => true,
                    'version' => 'wc/v3'
                ]
            );
        }
    }

?>