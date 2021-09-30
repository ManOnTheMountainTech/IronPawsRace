<?php
     namespace IronPaws;

     defined( 'ABSPATH' ) || exit;

     require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
     //require_once plugin_dir_path(__FILE__) . 'includes/autoloader.php';

    use Automattic\WooCommerce\Admin\Overrides\Order;

    // Called at the very end of the final order screen, as part of 
    // templates/order/order-detail-customer.php
    // @args: WC_Order -> The order object
     function ironpaws_order_details_after_customer_details(Order $order) {
      $teams_path = plugins_url("fetch-teams", __FILE__);

      if (!is_null($order)) {
        $wc_order_arg = $order->id;
        if ($wc_order_arg > 0) {
          $wc_order_id = WC_ORDER_ID;
          $teams_path .= "?{$wc_order_id}={$wc_order_arg}";
        }
      }

      // IE6/7 - non tables way:
      // 
      // <a href="$teams_path" style="display:inline-flex;flex-direction:row;aligns-items:center;vertical-align:center;line-height:5rem;height:5rem;">

      $icon = 'noun_hard_work_1154847.svg';
      $icon_abs_path = plugin_dir_url('ironpaws/img/icons/dogs/sleds/' . $icon);
      $icon_abs_path = $icon_abs_path . $icon;

      $next_steps = Strings::NEXT_STEPS;

      $alt_text=esc_html(__("A musher pulling their dog on a sled", "ironpaws"));
      $msg=__("Lookup the teams that are assigned to this race", "ironpaws");

      echo <<<ASK_LOCATION_REGISTRATION
      <p>$next_steps</p>
      <a href="$teams_path" class="img-a">
        <img 
          src="{$icon_abs_path}" 
          alt="{$alt_text}">
        <p class="p-aligned">{$msg}</p>
      </a>
      ASK_LOCATION_REGISTRATION;
    }
  ?>