<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class ScoreCard_CallBack_Args {
        function __construct() {
            $this->result = "";
            //$this->wc_rest = $wc_rest;
        }

        public string $result;
        //public WC_Rest $wc_rest;
    }
?>