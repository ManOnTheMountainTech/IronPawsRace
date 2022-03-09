<?php

namespace IronPawsLLC;

defined( 'ABSPATH' ) || exit;

include_once WP_PLUGIN_DIR .'/woocommerce/woocommerce.php';

use IronPawsLLC\Rest_API_Exception;

/* Refer to the following code pieces for understanding
    WP_REST_Server::respond_to_request
    current_user_can

    wp_get_current_user()
    wc_rest_check_manager_permissions( 'reports', 'read' )

        $objects = array(
            'reports'          => 'view_woocommerce_reports',
            'settings'         => 'manage_woocommerce',
            'system_status'    => 'manage_woocommerce',
            'attributes'       => 'manage_product_terms',
            'shipping_methods' => 'manage_woocommerce',
            'payment_gateways' => 'manage_woocommerce',
            'webhooks'         => 'manage_woocommerce',
        );
        
    return rest_ensure_response( wc()->api->get_endpoint_data( '/wc/store/cart' ) );
*/
class WC_Local_Rest_Client extends Abstract_REST_Client {

    // Constructs a WC_Local_Rest_Client
    // $api_base must begin with a '/'
    // i.e /wc/v3
    public function __construct(
        public string $api_base
    ) {
    }

    public function throwIfHasErrors($response) {
        if (is_wp_error($response)) {
            throw new WP_Exception($response);
        }

        $httpCode = $response->status;

        if ($httpCode < 200 || $httpCode > 202) {
            throw new Rest_API_Exception(__("HTTP request success was not assured."), $response);
        }

        $data = $response->data;
        if (!empty($data[WC_Rest::CODE])) {
            throw new Rest_API_Exception($data[WC_Rest::MESSAGE], $response);
        }
    }

    function do_get(string $api_operation, array $parameters = []): mixed {
        $request_uri = $this->api_base . '/' . $api_operation;
        $uri = parent::buildUrlQuery($request_uri, $parameters);
        $response = rest_ensure_response(\wc()->api->get_endpoint_data( $uri ));
        $this->throwIfHasErrors($response);
    }

    function get(string $api_operation, array $parameters = []): mixed {
        return $this->do_get($api_operation);
    }
}