<?php
    require  'c:/x64/php/vendor/autoload.php';

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;

    $woocommerce = new Client(
        'https://ironpaws.supermooseapps.com', 
        'ck_cca88420eaa709e2b43553db2ca5b3d50d6479f4', 
        'cs_c19ee6ea8b25b3ad309e279fd43fbcaafee32cfe',
        [
            'version' => 'wc/v3',
        ]
    );

    try {
        // Array of response results.
        $results = $woocommerce->get('customers');
        // Example: ['customers' => [[ 'id' => 8, 'created_at' => '2015-05-06T17:43:51Z', 'email' => ...
        echo '<pre><code>' . print_r( $results, true ) . '</code><pre>'; // JSON output.
    
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
    
    } catch (HttpClientException $e) {
        echo '<pre><code>' . print_r( $e->getMessage(), true ) . '</code><pre>'; // Error message.
        echo '<pre><code>' . print_r( $e->getRequest(), true ) . '</code><pre>'; // Last request data.
        echo '<pre><code>' . print_r( $e->getResponse(), true ) . '</code><pre>'; // Last response data.
    }
?>