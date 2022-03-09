<?php
    namespace IronPawsLLC;

    defined( 'ABSPATH' ) || exit;

    require_once 'includes/wp-defs.php';
    require_once 'includes/debug.php';
    //require_once 'includes/autoloader.php';

    class WC_Checkout_Hooks {
        function __construct() {
            Units::init();    
        }

        static function after_checkout_registration_form(\WC_Checkout $checkout ) {
            Units::init();

            echo '<h3>'.__('Race preferences').'</h3>';

            $distance_unit = __("Distance unit");
            $distance_unit_key = WP_Hooks::DISTANCE_UNIT;

            /*\woocommerce_form_field( self::KEY_DISTANCE_UNIT, array(
                'type'          => 'select',
                'label'         => $distance_unit,
                'required'      => true,
                'input_class'   =>  ['select-placeholder'],
                'options'       => array(
                    Units::MILES     => __( Units::$MILES_USER, 'ironpaws' ),
                    Units::KILOMETERS => __( Units::$KILOMETERS_USER, 'ironpaws' )
                )

            ),
            $checkout->get_value( self::KEY_DISTANCE_UNIT ) );*/

            $miles = Units::MILES;
            $miles_user = Units::$MILES_USER;
            $kilometers = Units::KILOMETERS;
            $kilometers_user = Units::$KILOMETERS_USER;
            $select_an_option = esc_html__("Select an option&hellip;", "ironpaws");
            
            echo <<<HTML
               <p class="form-row form-row-wide address-field validate-required validate-state" 
                    id="distance_unit_field" data-priority="110">
                    <label for="distance_unit" class="">{$distance_unit}
                        <abbr class="required" title="required">*</abbr>
                    </label>
                    <span class="woocommerce-input-wrapper">

                        <select name="{$distance_unit_key}" id="{$distance_unit_key}" class="state_select " 
                            autocomplete="address-level1" 
                            data-placeholder="$select_an_option"
                            data-input-classes="" 
                            data-label="{$distance_unit}">
                                <option value="">$select_an_option</option>
                                <option value="{$miles}" >$miles_user</option>
                                <option value="{$kilometers}" >$kilometers_user</option>
                        </select>
                    </span>
                </p>
                <p></p>
            HTML;
        }

        static function checkout_process() {
            $distance_unit = (string)sanitize_text_field($_POST[WP_Hooks::DISTANCE_UNIT]);
            if (empty($distance_unit) || (!((Units::MILES == $distance_unit) || (Units::KILOMETERS == $distance_unit)))) {
                \wc_add_notice('<strong>' . __( 'Please select a distance unit.', 'ironpaws' ) . '</strong>', 'error' );
            }
        }
    }
?>