<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    require_once 'includes/wp-defs.php';
    require_once 'includes/debug.php';
    //require_once 'includes/autoloader.php';

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;

    class WC_Checkout_Hooks {
        const KEY_DISTANCE_UNIT = 'distance_unit';

        static function selected_field() {
            $field   = '';
            $options = '';

            if ( ! empty( $args['options'] ) ) {
                foreach ( $args['options'] as $option_key => $option_text ) {
                    if ( '' === $option_key ) {
                        // If we have a blank option, select2 needs a placeholder.
                        if ( empty( $args['placeholder'] ) ) {
                            $args['placeholder'] = $option_text ? $option_text : __( 'Choose an option', 'woocommerce' );
                        }
                        $custom_attributes[] = 'data-allow_clear="true"';
                    }
                    $options .= '<option value="' . esc_attr( $option_key ) . '" ' . selected( $value, $option_key, false ) . '>' . esc_html( $option_text ) . '</option>';
                }

                $field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ) . '">
                        ' . $options . '
                    </select>';
            }
        }

        static function after_checkout_registration_form(\WC_Checkout $checkout ) {
            echo '<h3>'.__('Race preferences').'</h3>';

            \woocommerce_form_field( self::KEY_DISTANCE_UNIT, array(
                'type'          => 'select',
                'label'         => __( 'Distance unit' ),
                'required'      => true,
                'placeholder'   => __( 'foo' ),
                'options'       => array(
                    ''             => __( 'Please select a unit of measurement', 'ironpaws' ),
                    'miles'     => __( 'Miles', 'ironpaws' ),
                    'kilometers' => __( 'Kilometers', 'ironpaws' )
                )

            ),

            $checkout->get_value( self::KEY_DISTANCE_UNIT ) );     
        }

        // $fields->type can be:
        //  'checkbox'
        //  'multiselect'-> array elements seperated by comma
        //  'textarea'
        //  'password'
        static function checkout_fields(?array $fields) {
            //var_dump($fields);
            $fields['shipping']['preferences'] = array(
                'label'     => __('Distance Units', 'ironpaws'),
                'placeholder'   => _x('miles', 'placeholder', 'ironpaws'),
                'required'  => true
            );

            return $fields;
        }
    }

?>