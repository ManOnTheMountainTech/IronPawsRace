<?php
    defined( 'ABSPATH' ) || exit;

    namespace IronPaws;

    require_once plugin_dir_path(__FILE__) . 'wc-rest.php';
    require_once plugin_dir_path(__FILE__) . 'container-html-pattern.php';

    class Orders implements Container_HTML_Pattern {
        /*protected $orders;
        int i;

        function getCurrentOrders() {
            $cur_user = wp_get_current_user();

            $wc_rest_api = new WC_Rest();
            $orders = $wc_rest_api->getOrdersByCustomerId($cur_user->ID);
            return $orders;
        }

        function makeOpeningHTML() {
            return <<<GET_ORDERS
                <label for="Orders">Please select an order</label>
                <select name="Orders" id="{$team_name_id}">
            GET_ORDERS;
        }

        function makeListItemHTML(array $row) {
            if ($orders->checkRaceEditable_noThrow()) {
                return '<option value="' . $row[0] . '">' . $row[1] .  '</option>';
            }
        }

        function makeClosingHTML() {
            return "</select></br>";
        }*/
    }
?>