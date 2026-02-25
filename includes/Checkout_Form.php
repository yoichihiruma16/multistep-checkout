<?php 

namespace MultiStep_Checkout;

/**
 * The Checkout Form Handler Class
 */
class Checkout_Form {

    function __construct() {
        add_filter( 'msc_checkout_steps', array( $this, 'add_custom_form_step' ), 10, 1 );
        add_filter( 'msc_checkout_content', array( $this, 'add_custom_form_content' ), 10, 1 );
        add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout_forms' ), 10 );
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_formidable_data_to_order' ), 10, 2 );
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'populate_billing_from_formidable' ), 10, 2 );
        add_action( 'add_meta_boxes', array( $this, 'add_form_info_meta_box' ) );
        
        // Add to REST API
        add_action( 'rest_api_init', array( $this, 'register_rest_api_fields' ) );
    }

    /**
     * Remove submit button from Formidable form
     */
    public function remove_submit_button( $button, $form ) {
        // Return a valid structure with [button_action] placeholder but with empty content
        return '[button_action]';
    }

    /**
     * Remove submit button classes
     */
    public function remove_submit_button_classes( $classes, $form ) {
        return array();
    }

    /**
     * Remove form tag from Formidable form
     */
    public function remove_form_tag( $include, $form ) {
        return false;
    }

    /**
     * Get all unique form IDs from cart items
     */
    public function get_cart_form_ids() {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return array();
        }

        $cart_items = WC()->cart->get_cart();
        $form_ids = array();
        
        foreach ( $cart_items as $cart_item ) {
            if ( isset( $cart_item['product_id'] ) ) {
                // Try to get multiple forms first
                $product_form_ids = get_post_meta( $cart_item['product_id'], '_msc_custom_form_ids', true );
                
                // Fallback to single form for backward compatibility
                if ( empty( $product_form_ids ) ) {
                    $product_form_ids = get_post_meta( $cart_item['product_id'], '_msc_custom_form_id', true );
                    if ( ! empty( $product_form_ids ) ) {
                        $product_form_ids = array( $product_form_ids );
                    }
                }
                
                // Add forms to the list if not already included
                if ( ! empty( $product_form_ids ) && is_array( $product_form_ids ) ) {
                    foreach ( $product_form_ids as $form_id ) {
                        if ( ! in_array( $form_id, $form_ids ) ) {
                            $form_ids[] = $form_id;
                        }
                    }
                }
            }
        }
        
        return $form_ids;
    }

    /**
     * Check if any cart item has a custom form assigned (legacy function)
     */
    public function get_cart_form_id() {
        $form_ids = $this->get_cart_form_ids();
        return ! empty( $form_ids ) ? $form_ids[0] : false;
    }

    /**
     * Add custom form step to checkout steps
     */
    public function add_custom_form_step( $steps ) {
        $form_ids = $this->get_cart_form_ids();
        
        if ( ! empty( $form_ids ) ) {
            foreach ( $form_ids as $index => $form_id ) {
                // Get the form name from Formidable Forms
                $form = \FrmForm::getOne( $form_id );
                $form_name = $form ? $form->name : esc_html__( 'Additional Information', 'multistep-checkout' );
                
                $steps[] = array(
                    'id' => 'opc-custom-form-' . $form_id,
                    'title' => $form_name,
                );
            }
        }
        
        return $steps;
    }

    /**
     * Add custom form content to checkout
     */
    public function add_custom_form_content( $content ) {
        $form_ids = $this->get_cart_form_ids();
        
        if ( ! empty( $form_ids ) ) {
            foreach ( $form_ids as $index => $form_id ) {
                ob_start();
                
                // Remove submit button and form tag using Formidable's filters
                add_filter( 'frm_submit_button_html', array( $this, 'remove_submit_button' ), 99, 2 );
                add_filter( 'frm_submit_button_class', array( $this, 'remove_submit_button_classes' ), 99, 2 );
                add_filter( 'frm_include_form_tag', array( $this, 'remove_form_tag' ), 99, 2 );
                
                ?>
                <div id="checkout-step-custom-form-<?php echo esc_attr( $form_id ); ?>" class="a-item" style="display: none;">
                    <div class="custom-form-wrapper">
                        <?php 
                        // Render the Formidable form without submit button and form tag
                        echo do_shortcode( '[formidable id="' . esc_attr( $form_id ) . '"]' );
                        ?>
                    </div>
                    <button type="button" class="button prev-btn"><span><?php esc_html_e( 'Previous', 'multistep-checkout' ); ?></span></button>
                    <button type="button" class="button next-btn"><span><?php esc_html_e( 'Continue', 'multistep-checkout' ); ?></span></button>
                </div>
                <?php
                
                // Remove the filters after rendering
                remove_filter( 'frm_submit_button_html', array( $this, 'remove_submit_button' ), 99 );
                remove_filter( 'frm_submit_button_class', array( $this, 'remove_submit_button_classes' ), 99 );
                remove_filter( 'frm_include_form_tag', array( $this, 'remove_form_tag' ), 99 );
                
                $content[] = ob_get_clean();
            }
        }
        
        return $content;
    }

    /**
     * Validate all checkout forms
     */
    public function validate_checkout_forms() {
        $form_ids = $this->get_cart_form_ids();
        
        if ( empty( $form_ids ) ) {
            return;
        }

        // Get all form submissions
        foreach ( $form_ids as $form_id ) {
            // Check if this form should be displayed based on product selection
            if ( ! Form_Validator::should_display_form( $form_id ) ) {
                continue;
            }

            // Validate the form
            $validation = Form_Validator::validate_form_submission( $form_id, $_POST );
            
            if ( ! $validation['valid'] && ! empty( $validation['errors'] ) ) {
                foreach ( $validation['errors'] as $error ) {
                    wc_add_notice( $error, 'error' );
                }
            }
        }
    }

    /**
     * Save Formidable form data to order meta
     */
    public function save_formidable_data_to_order( $order_id, $data ) {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return;
        }

        $cart_items = WC()->cart->get_cart();
        $all_form_data = array();

        foreach ( $cart_items as $cart_item_key => $cart_item ) {
            if ( ! isset( $cart_item['product_id'] ) ) {
                continue;
            }

            $product_id = $cart_item['product_id'];
            
            // Get multiple forms or single form
            $form_ids = get_post_meta( $product_id, '_msc_custom_form_ids', true );
            if ( empty( $form_ids ) ) {
                $form_ids = get_post_meta( $product_id, '_msc_custom_form_id', true );
                if ( ! empty( $form_ids ) ) {
                    $form_ids = array( $form_ids );
                }
            }
            
            if ( empty( $form_ids ) || ! is_array( $form_ids ) ) {
                continue;
            }

            // Get product information
            $product = wc_get_product( $product_id );
            $product_name = $product ? $product->get_name() : __( 'Unknown Product', 'multistep-checkout' );

            // Loop through each form for this product
            foreach ( $form_ids as $form_id ) {
                // Get the form object
                $form = \FrmForm::getOne( $form_id );
                if ( ! $form ) {
                    continue;
                }

                // Get all fields for this form
                $fields = \FrmField::get_all_for_form( $form_id );
                $form_data = array(
                    'form_id' => $form_id,
                    'form_name' => $form->name,
                    'product_id' => $product_id,
                    'product_name' => $product_name,
                    'fields' => array(),
                );

                foreach ( $fields as $field ) {
                    // Skip non-input fields (divider, html, etc.)
                    if ( in_array( $field->type, array( 'divider', 'html', 'break' ) ) ) {
                        continue;
                    }
                    
                    // Get the field value from POST data
                    $value = null;
                    
                    // Check if item_meta array exists
                    if ( isset( $_POST['item_meta'] ) && is_array( $_POST['item_meta'] ) ) {
                        // Check if this field ID exists in the item_meta array
                        if ( isset( $_POST['item_meta'][ $field->id ] ) ) {
                            $value = $_POST['item_meta'][ $field->id ];
                        }
                    }
                    
                    if ( $value !== null && $value !== '' ) {
                        // Handle array values (checkboxes, multi-select, etc.)
                        if ( is_array( $value ) ) {
                            $value = implode( ', ', array_map( 'sanitize_text_field', $value ) );
                        } else {
                            $value = sanitize_text_field( $value );
                        }
                        
                        $form_data['fields'][] = array(
                            'field_id' => $field->id,
                            'field_key' => $field->field_key,
                            'field_name' => $field->name,
                            'field_label' => $field->name,
                            'field_type' => $field->type,
                            'value' => $value,
                        );
                    }
                }

                if ( ! empty( $form_data['fields'] ) ) {
                    $all_form_data[] = $form_data;
                }
            }
        }

        // Save all form data to order meta
        if ( ! empty( $all_form_data ) ) {
            update_post_meta( $order_id, '_formidable_forms_data', $all_form_data );
        }
    }

    /**
     * Populate WooCommerce billing and shipping fields from Formidable form data
     * Maps the "Abonnee" form fields to billing address fields
     */
    public function populate_billing_from_formidable( $order_id, $data ) {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return;
        }

        $cart_items = WC()->cart->get_cart();
        $form_data = get_post_meta( $order_id, '_formidable_forms_data', true );
        
        if ( empty( $form_data ) || ! is_array( $form_data ) ) {
            return;
        }

        // Look for the "Abonnee" form in the captured form data
        $abonnee_form = null;
        foreach ( $form_data as $form ) {
            if ( stripos( $form['form_name'], 'Abonnee' ) !== false || stripos( $form['form_name'], 'abonnee' ) !== false ) {
                $abonnee_form = $form;
                break;
            }
        }

        if ( empty( $abonnee_form ) || empty( $abonnee_form['fields'] ) ) {
            return;
        }

        // Extract field values from the Abonnee form
        $form_values = array();
        foreach ( $abonnee_form['fields'] as $field ) {
            $form_values[ $field['field_name'] ] = $field['value'];
            // Also store by field type for easier mapping
            $form_values[ $field['field_type'] . '_' . $field['field_name'] ] = $field['value'];
        }

        // Get the order object
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Map Formidable fields to WooCommerce billing fields
        // The mapping is based on the Abonnee form structure

        // Handle Name field (type: name)
        $name_value = '';
        $first_name = '';
        $last_name = '';
        
        // Try to find the name field
        foreach ( $abonnee_form['fields'] as $field ) {
            if ( $field['field_type'] === 'name' ) {
                // The value might contain the full name or structured data
                $name_value = $field['value'];
                // Try to parse if it's a structured value
                if ( is_array( json_decode( $name_value, true ) ) ) {
                    $name_data = json_decode( $name_value, true );
                    $first_name = isset( $name_data['first'] ) ? $name_data['first'] : '';
                    $last_name = isset( $name_data['last'] ) ? $name_data['last'] : '';
                } else {
                    // Split the name value into first and last name
                    $name_parts = explode( ' ', trim( $name_value ), 2 );
                    $first_name = isset( $name_parts[0] ) ? $name_parts[0] : '';
                    $last_name = isset( $name_parts[1] ) ? $name_parts[1] : '';
                }
                break;
            }
        }

        // Set billing first name and last name
        if ( ! empty( $first_name ) ) {
            $order->set_billing_first_name( $first_name );
        }
        if ( ! empty( $last_name ) ) {
            $order->set_billing_last_name( $last_name );
        }

        // Handle Email field
        foreach ( $abonnee_form['fields'] as $field ) {
            if ( $field['field_type'] === 'email' ) {
                $order->set_billing_email( $field['value'] );
                break;
            }
        }

        // Handle Phone field (prefer mobile phone if available)
        $phone_value = '';
        foreach ( $abonnee_form['fields'] as $field ) {
            if ( $field['field_type'] === 'phone' ) {
                // Prefer mobile phone (Telefoonnummer (mobiel))
                if ( stripos( $field['field_name'], 'mobiel' ) !== false ) {
                    $phone_value = $field['value'];
                    break;
                }
                // Fall back to fixed phone if mobile not found yet
                if ( empty( $phone_value ) ) {
                    $phone_value = $field['value'];
                }
            }
        }
        if ( ! empty( $phone_value ) ) {
            $order->set_billing_phone( $phone_value );
        }

        // Handle Address field (type: address)
        foreach ( $abonnee_form['fields'] as $field ) {
            if ( $field['field_type'] === 'address' ) {
                // The address value is typically a string or JSON structure
                $address_data = $field['value'];
                
                // Try to parse as JSON if it looks like JSON
                if ( is_string( $address_data ) && ( strpos( $address_data, '{' ) === 0 ) ) {
                    $address_array = json_decode( $address_data, true );
                } else {
                    // Try to parse it as a structured format
                    $address_array = array();
                }

                // Extract address components
                $street = isset( $address_array['line1'] ) ? $address_array['line1'] : '';
                $number = isset( $address_array['line2'] ) ? $address_array['line2'] : '';
                $city = isset( $address_array['city'] ) ? $address_array['city'] : '';
                $postcode = isset( $address_array['zip'] ) ? $address_array['zip'] : '';
                $country = isset( $address_array['country'] ) ? $address_array['country'] : '';

                // Set billing address
                if ( ! empty( $street ) ) {
                    // Combine street and number if number exists
                    if ( ! empty( $number ) ) {
                        $street = $street . ' ' . $number;
                    }
                    $order->set_billing_address_1( $street );
                }
                if ( ! empty( $city ) ) {
                    $order->set_billing_city( $city );
                }
                if ( ! empty( $postcode ) ) {
                    $order->set_billing_postcode( $postcode );
                }
                if ( ! empty( $country ) ) {
                    $order->set_billing_country( $country );
                }

                break;
            }
        }

        // Set shipping address same as billing (as requested)
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();
        $billing_address_1 = $order->get_billing_address_1();
        $billing_city = $order->get_billing_city();
        $billing_postcode = $order->get_billing_postcode();
        $billing_country = $order->get_billing_country();
        $billing_email = $order->get_billing_email();
        $billing_phone = $order->get_billing_phone();

        if ( ! empty( $billing_first_name ) ) {
            $order->set_shipping_first_name( $billing_first_name );
        }
        if ( ! empty( $billing_last_name ) ) {
            $order->set_shipping_last_name( $billing_last_name );
        }
        if ( ! empty( $billing_address_1 ) ) {
            $order->set_shipping_address_1( $billing_address_1 );
        }
        if ( ! empty( $billing_city ) ) {
            $order->set_shipping_city( $billing_city );
        }
        if ( ! empty( $billing_postcode ) ) {
            $order->set_shipping_postcode( $billing_postcode );
        }
        if ( ! empty( $billing_country ) ) {
            $order->set_shipping_country( $billing_country );
        }

        // Save the order with updated billing and shipping data
        $order->save();
    }

    /**
     * Add meta box for form information
     */
    public function add_form_info_meta_box() {
        add_meta_box(
            'msc_form_information',
            esc_html__( 'Form Information', 'multistep-checkout' ),
            array( $this, 'display_formidable_data_in_admin' ),
            'shop_order',
            'normal',
            'default'
        );
        
        // For HPOS (High-Performance Order Storage)
        add_meta_box(
            'msc_form_information',
            esc_html__( 'Form Information', 'multistep-checkout' ),
            array( $this, 'display_formidable_data_in_admin' ),
            'woocommerce_page_wc-orders',
            'normal',
            'default'
        );
    }

    /**
     * Display Formidable form data in admin order page
     */
    public function display_formidable_data_in_admin( $post_or_order ) {
        // Handle both post object and order object
        if ( is_a( $post_or_order, 'WP_Post' ) ) {
            $order_id = $post_or_order->ID;
        } elseif ( is_a( $post_or_order, 'WC_Order' ) ) {
            $order_id = $post_or_order->get_id();
        } else {
            return;
        }
        
        $form_data = get_post_meta( $order_id, '_formidable_forms_data', true );
        
        if ( empty( $form_data ) ) {
            echo '<p style="color: #666;">' . esc_html__( 'No form data available for this order.', 'multistep-checkout' ) . '</p>';
            return;
        }
        
        // Show form data
        foreach ( $form_data as $form ) {
            echo '<div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">';
            
            // Display form name and product info
            echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #2271b1;">';
            echo '<h4 style="margin: 0;">' . esc_html( $form['form_name'] ) . '</h4>';
            
            if ( ! empty( $form['product_name'] ) ) {
                echo '<span style="background: #2271b1; color: white; padding: 5px 12px; border-radius: 3px; font-size: 12px; font-weight: 600;">';
                echo esc_html( $form['product_name'] );
                echo '</span>';
            }
            
            echo '</div>';
            
            if ( empty( $form['fields'] ) ) {
                echo '<p style="color: #d63638; margin: 0;">' . esc_html__( 'No fields captured.', 'multistep-checkout' ) . '</p>';
            } else {
                echo '<table class="widefat striped" style="margin-top: 0; background: white;">';
                echo '<thead><tr>';
                echo '<th style="width: 35%; padding: 10px;">' . esc_html__( 'Field', 'multistep-checkout' ) . '</th>';
                echo '<th style="padding: 10px;">' . esc_html__( 'Value', 'multistep-checkout' ) . '</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                
                foreach ( $form['fields'] as $field ) {
                    echo '<tr>';
                    echo '<td style="font-weight: 600; padding: 10px;">' . esc_html( $field['field_label'] ) . '</td>';
                    echo '<td style="padding: 10px;">' . wp_kses_post( nl2br( $field['value'] ) ) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
            }
            
            echo '</div>';
        }
    }

    /**
     * Register REST API fields for form information
     */
    public function register_rest_api_fields() {
        register_rest_field(
            'shop_order',
            'formidable_forms_data',
            array(
                'get_callback' => array( $this, 'get_form_data_for_api' ),
                'update_callback' => null,
                'schema' => array(
                    'description' => __( 'Formidable Forms data submitted with the order', 'multistep-checkout' ),
                    'type' => 'array',
                    'context' => array( 'view', 'edit' ),
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'form_id' => array(
                                'type' => 'integer',
                                'description' => __( 'Formidable Form ID', 'multistep-checkout' ),
                            ),
                            'form_name' => array(
                                'type' => 'string',
                                'description' => __( 'Formidable Form Name', 'multistep-checkout' ),
                            ),
                            'product_id' => array(
                                'type' => 'integer',
                                'description' => __( 'Product ID', 'multistep-checkout' ),
                            ),
                            'product_name' => array(
                                'type' => 'string',
                                'description' => __( 'Product Name', 'multistep-checkout' ),
                            ),
                            'fields' => array(
                                'type' => 'array',
                                'description' => __( 'Form fields and values', 'multistep-checkout' ),
                                'items' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'field_id' => array( 'type' => 'integer' ),
                                        'field_key' => array( 'type' => 'string' ),
                                        'field_name' => array( 'type' => 'string' ),
                                        'field_label' => array( 'type' => 'string' ),
                                        'field_type' => array( 'type' => 'string' ),
                                        'value' => array( 'type' => 'string' ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Get form data for REST API response
     */
    public function get_form_data_for_api( $object ) {
        $order_id = $object['id'];
        $form_data = get_post_meta( $order_id, '_formidable_forms_data', true );
        
        if ( empty( $form_data ) || ! is_array( $form_data ) ) {
            return array();
        }
        
        return $form_data;
    }
}
