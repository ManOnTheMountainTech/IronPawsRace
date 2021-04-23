<?php
    require_once('includes/wp-defs.php');
    require_once(plugin_dir_path(__FILE__) . 'includes/debug.php');
    require plugin_dir_path(__FILE__) . 'vendor/autoload.php';

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;

    /*
    function ironpaws_woocommerce_order_status_completed( $order_id ) {
        echo 'woocommerce_order_status_completed';
        error_log( "Order complete for order $order_id", 0 );
    }

    function ironpaws_woocommerce_payment_complete_order_status( $order_id ) {
        echo 'woocommerce_payment_complete_order_status';
        error_log( "Status of payment complete for order $order_id", 0 );
    }*/

    function create_wc() {
        return new Client(
            'https://ironpaws.supermooseapps.com', 
            'ck_cca88420eaa709e2b43553db2ca5b3d50d6479f4', 
            'cs_c19ee6ea8b25b3ad309e279fd43fbcaafee32cfe',
            [
                'version' => 'wc/v3',
            ]
        );
    }

    function getResponseBody($response) {
        return $woocommerce->http->getResponse()->getBody();
    }

    // @function processResponse
    // @param $result - result from a call to the WooCommerce REST API
    // @returns : null = failure, associative object = success
    // ----
    // Internally, Client calls HttpClient, which sets the parameters as CURL
    // options, and then calls CURL. The result is processed by json_decode.
    // json_decode can return true, false, or null. However, the body still
    // may contain valid data. Try a little harder to get it.
    function processResponse($result) {
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

    /* Order is done, let's move on */
    function ironpaws_woocommerce_payment_complete( $order_id ) {
        error_log( "payment complete for order $order_id", 0 );

        try {
            // Array of response results.
            // $results = create_wc()->get('customers');
            // Example: ['customers' => [[ 'id' => 8, 'created_at' => '2015-05-06T17:43:51Z', 'email' => ...

            wp_remote_post('/reg-a-team', array('wc_order_id' => $order_id));
            //return '<pre><code>' . print_r( $results, true ) . '</code><pre>'; // JSON output.
        
            /*
            // Last request data.
            $lastRequest = $woocommerce->http->getRequest();
            echo '<pre><code>' . print_r( $lastRequest->getUrl(), true ) . '</code><pre>'; // Requested URL (string).
            echo '<pre><code>' . print_r( $lastRequest->getMethod(), true ) . '</code><pre>'; // Request method (string).
            echo '<pre><code>' . print_r( $lastRequest->getParameters(), true ) . '</code><pre>'; // Request parameters (array).
            echo '<pre><code>' . print_r( $lastRequest->getHeaders(), true ) . '</code><pre>'; // Request headers (array).
            echo '<pre><code>' . print_r( $lastRequest->getBody(), true ) . '</code><pre>'; // Request body (JSON).
        
            // Last response data.
            $lastResponse = $woocommerce->http->getResponse();
            echo '<pre><code>' . print_r( $lastResponse->getCode(), true ) . '</code><pre>'; // Response code (int).
            echo '<pre><code>' . print_r( $lastResponse->getHeaders(), true ) . '</code><pre>'; // Response headers (array).
            echo '<pre><code>' . print_r( $lastResponse->getBody(), true ) . '</code><pre>'; // Response body (JSON).
            */
        
        } catch (HttpClientException $e) {
            write_log( '<pre><code>' . $e->getMessage() . '</code><pre>'); // Error message.
            write_log( '<pre><code>' . $e->getRequest() . '</code><pre>'); // Last request data.
            write_log( '<pre><code>' . $e->getResponse() . '</code><pre>'); // Last response data.
        }
    }
?>