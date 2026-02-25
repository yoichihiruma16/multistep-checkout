<?php 

namespace MultiStep_Checkout;

/**
 * Form Validator Class
 * Handles conditional form rendering and validation based on product selection
 */
class Form_Validator {

    /**
     * Field Labels Mapping
     * Maps field keys to human-readable labels for error messages
     */
    private static $field_labels = array(
        // Abonnee Form
        'field_2wnsd' => 'Salutation',
        'field_f9iyg' => 'Name',
        'field_eqy7c' => 'Birth Date',
        'field_uq2ba' => 'Email',
        'field_tz4l3' => 'Phone (Fixed)',
        'field_zzljl' => 'Phone (Mobile)',
        'field_qbgd5' => 'Address',
        
        // Contactpersoon Form
        'field_wgadv' => 'Salutation',
        'field_g5aeg' => 'Name',
        'field_2hsej' => 'Phone',
        'field_d82tp' => 'Email Address',
        'field_7sglw' => 'Relation to Contact Person',
        
        // Alarmopvolgers Form
        'field_wm6yq' => 'Is Contact Person Also Alarm Follower',
        'field_29ib6' => 'Salutation',
        'field_nwipl' => 'Name',
        'field_os7by' => 'Phone',
        
        // Verpleegkundige Opvolging Form
        'field_bhw8o' => 'Medical Data Sharing Agreement',
        
        // Woningtoegang Form
        'field_d6kg7' => 'Central Door Access',
        'field_kul5l' => 'Key Box Permission',
        'field_ysk43' => 'Home Care Status',
        'field_15g4p' => 'Home Care Provider Name',
        'field_d6xpj' => 'Home Care Key Box Access',
        
        // Aanvullende Informatie Form
        'field_2ux5r' => 'Remarks',
        'field_v29yb' => 'Bank Account Number',
        'field_g0rt2' => 'Automatic Debit Authorization',
        'field_uxnah' => 'Terms and Conditions Agreement',
        'field_jv432' => 'Data Processing and Privacy Agreement',
        
        // Alarm via Verzekering Form
        'field_ppiky2' => 'Alarm Device Provider',
    );

    /**
     * Form Requirements by Form ID
     */
    private static $form_requirements = array(
        3 => array(
            'name' => 'Abonnee',
            'required_fields' => array( 'field_2wnsd', 'field_f9iyg', 'field_eqy7c', 'field_uq2ba', 'field_tz4l3', 'field_zzljl', 'field_qbgd5' ),
            'conditional' => false,
        ),
        4 => array(
            'name' => 'Contactpersoon',
            'required_fields' => array( 'field_wgadv', 'field_g5aeg', 'field_2hsej', 'field_d82tp', 'field_7sglw' ),
            'conditional' => false,
        ),
        5 => array(
            'name' => 'Alarmopvolgers',
            'required_fields' => array( 'field_wm6yq', 'field_29ib6', 'field_nwipl', 'field_os7by' ),
            'conditional' => false,
        ),
        6 => array(
            'name' => 'Verpleegkundige opvolging',
            'required_fields' => array( 'field_bhw8o' ),
            'conditional' => false,
        ),
        7 => array(
            'name' => 'Woningtoegang',
            'required_fields' => array( 'field_d6kg7', 'field_kul5l', 'field_ysk43' ),
            'conditional' => true,
            'conditional_fields' => array(
                'field_15g4p' => array( 'parent' => 'field_ysk43', 'value' => 'Ja' ),
                'field_d6xpj' => array( 'parent' => 'field_kul5l', 'value' => 'Ja' ),
            ),
        ),
        10 => array(
            'name' => 'Aanvullende informatie',
            'required_fields' => array( 'field_uxnah', 'field_jv432' ),
            'conditional' => true,
            'conditional_fields' => array(
                'field_v29yb' => array( 'parent' => 'field_g0rt2', 'value' => 'checked' ),
            ),
        ),
        11 => array(
            'name' => 'Alarm via verzekering',
            'required_fields' => array( 'field_ppiky2' ),
            'conditional' => false,
        ),
    );

    /**
     * Get relevant forms based on cart items
     */
    public static function get_relevant_forms() {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return array();
        }

        $cart_items = WC()->cart->get_cart();
        $form_ids = array();
        
        // Check each cart item for product type/attributes
        foreach ( $cart_items as $cart_item ) {
            if ( ! isset( $cart_item['product_id'] ) ) {
                continue;
            }

            $product = wc_get_product( $cart_item['product_id'] );
            if ( ! $product ) {
                continue;
            }

            // Check product type and attributes
            $product_type = $product->get_type();
            $product_attrs = $product->get_attributes();

            // Determine form set based on product attributes or meta
            $forms = self::get_forms_for_product( $product );
            
            foreach ( $forms as $form_id ) {
                if ( ! in_array( $form_id, $form_ids ) ) {
                    $form_ids[] = $form_id;
                }
            }
        }

        // Return forms defined in product meta if available
        return ! empty( $form_ids ) ? $form_ids : array( 3, 4, 5, 7, 10 );
    }

    /**
     * Get forms for a specific product
     * Reads form IDs from product meta: _msc_custom_form_ids
     */
    private static function get_forms_for_product( $product ) {
        $product_id = $product->get_id();
        
        // First check product meta for multiple forms
        $form_ids = get_post_meta( $product_id, '_msc_custom_form_ids', true );
        if ( ! empty( $form_ids ) && is_array( $form_ids ) ) {
            return $form_ids;
        }

        // Fallback to single form meta
        $form_id = get_post_meta( $product_id, '_msc_custom_form_id', true );
        if ( ! empty( $form_id ) ) {
            return is_array( $form_id ) ? $form_id : array( $form_id );
        }

        // Return empty array - no forms defined for this product
        return array();
    }

    /**
     * Validate form submission
     */
    public static function validate_form_submission( $form_id, $post_data ) {
        if ( ! isset( self::$form_requirements[ $form_id ] ) ) {
            return array( 'valid' => true );
        }

        $form_req = self::$form_requirements[ $form_id ];
        $errors = array();

        // Check required fields
        foreach ( $form_req['required_fields'] as $field_key ) {
            $field_value = self::get_field_value( $field_key, $post_data );
            
            if ( empty( $field_value ) ) {
                $field_label = self::get_field_label( $field_key );
                $errors[] = sprintf(
                    __( '%s in %s is required', 'multistep-checkout' ),
                    $field_label,
                    $form_req['name']
                );
            }
        }

        // Check conditional fields
        if ( $form_req['conditional'] && isset( $form_req['conditional_fields'] ) ) {
            foreach ( $form_req['conditional_fields'] as $field_key => $condition ) {
                $parent_value = self::get_field_value( $condition['parent'], $post_data );
                
                // If parent condition is met, check if child field is required
                if ( $parent_value === $condition['value'] ) {
                    $child_value = self::get_field_value( $field_key, $post_data );
                    if ( empty( $child_value ) ) {
                        $field_label = self::get_field_label( $field_key );
                        $errors[] = sprintf(
                            __( '%s in %s is required based on previous selection', 'multistep-checkout' ),
                            $field_label,
                            $form_req['name']
                        );
                    }
                }
            }
        }

        return array(
            'valid' => empty( $errors ),
            'errors' => $errors,
        );
    }

    /**
     * Get field value from POST data
     */
    private static function get_field_value( $field_key, $post_data ) {
        // Check in item_meta
        if ( isset( $post_data['item_meta'] ) && is_array( $post_data['item_meta'] ) ) {
            foreach ( $post_data['item_meta'] as $meta_value ) {
                if ( is_array( $meta_value ) && isset( $meta_value['field_key'] ) && $meta_value['field_key'] === $field_key ) {
                    return $meta_value['value'];
                }
            }
        }

        // Check in POST directly
        if ( isset( $_POST[ 'field_' . $field_key ] ) ) {
            return $_POST[ 'field_' . $field_key ];
        }

        return null;
    }

    /**
     * Get human-readable label for a field
     */
    private static function get_field_label( $field_key ) {
        if ( isset( self::$field_labels[ $field_key ] ) ) {
            return self::$field_labels[ $field_key ];
        }
        // Fallback to field key if label not found
        return $field_key;
    }

    /**
     * Get form requirements for display
     */
    public static function get_form_requirements( $form_id ) {
        return isset( self::$form_requirements[ $form_id ] ) ? self::$form_requirements[ $form_id ] : array();
    }

    /**
     * Check if form should be visible
     */
    public static function should_display_form( $form_id ) {
        $relevant_forms = self::get_relevant_forms();
        return in_array( $form_id, $relevant_forms );
    }
}
