<?php

namespace IronPawsLLC;

use IronPawsLLC\Rest_API_Exception;

// Taken from:
// https://www.damiencarbery.com/2019/06/woocommerce-rest-api-authentication/

class WC_OAuth1_Client {
    public function __construct(
        public string $api_base,
        protected string $api_key,
        protected string $api_secret
    ) {
    }

    function join_params( $params ) {
        $query_params = array();

        foreach ( $params as $param_key => $param_value ) {
            $string = $param_key . '=' . $param_value;
            $query_params[] = str_replace( array( '+', '%7E' ), array( ' ', '~' ), rawurlencode( $string ) );
        }
        
        return implode( '%26', $query_params );
    }

    function get(string $api_operation): string {
        // Request URI.
        $request_uri = $this->api_base . $api_operation;

        // Unique once-off parameters.
        $nonce = uniqid();
        $timestamp = time();

        $oauth_signature_method = 'HMAC-SHA1';

        $hash_algorithm = strtolower( str_replace( 'HMAC-', '', $oauth_signature_method ) ); // sha1
        $secret = $this->consumer_secret . '&';

        $http_method = 'GET';
        $base_request_uri = rawurlencode( $request_uri );
        $params = array( 'oauth_consumer_key' => $this->consumer_key, 'oauth_nonce' => $nonce, 'oauth_signature_method' => 'HMAC-SHA1', 'oauth_timestamp' => $timestamp );
        $query_string = $this->join_params( $params );

        $string_to_sign = $http_method . '&' . $base_request_uri . '&' . $query_string;
        $oauth_signature = base64_encode( hash_hmac( $hash_algorithm, $string_to_sign, $secret, true ) );

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $request_uri,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $http_method,
        CURLOPT_HTTPHEADER => array(
            "Accept: */*",
            "Authorization: OAuth oauth_consumer_key=\"".$this->api_key."\",oauth_signature_method=\"".$oauth_signature_method."\",oauth_timestamp=\"".$timestamp."\",oauth_nonce=\"".$nonce."\",oauth_signature=\"".$oauth_signature."\"",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "Host: localhost",
            "User-Agent: IronPaws_wc-oauth1-client/1.0.0",
            "accept-encoding: gzip, deflate",
            "cache-control: no-cache"
        ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new Rest_API_Exception("cURL Error #:" . $err);
        }

        return $response;
    }
}