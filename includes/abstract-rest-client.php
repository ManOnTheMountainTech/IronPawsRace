<?php

namespace IronPawsLLC;

defined( 'ABSPATH' ) || exit;

include_once WP_PLUGIN_DIR .'/woocommerce/woocommerce.php';

abstract class Abstract_REST_Client {

    public function __construct(

        // The base of the api relative to wp-jason/
        public string $api_base,
    ) {
    }

    /**
     * Build URL.
     *
     * @param string $url        URL.
     * @param array  $parameters Query string parameters.
     *
     * @return string
     */
    protected function buildUrlQuery($url, $parameters = [])
    {
        if (!empty($parameters)) {
            $url .= '?' . \http_build_query($parameters);
        }

        return $url;
    }

    abstract function get(string $api_operation, array $parameters = []): mixed;
}