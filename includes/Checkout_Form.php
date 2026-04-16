<?php 

namespace MultiStep_Checkout;

/**
 * The Checkout Form Handler Class
 */
class Checkout_Form {

    function __construct() {
        add_filter( 'msc_checkout_steps', array( $this, 'add_custom_form_step' ), 10, 1 );
        add_filter( 'msc_checkout_content', array( $this, 'add_custom_form_content' ), 10, 1 );
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_formidable_data_to_order' ), 10, 2 );
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'populate_billing_from_formidable' ), 10, 2 );
        add_action( 'add_meta_boxes', array( $this, 'add_form_info_meta_box' ) );
        
        // Add to REST API
        add_action( 'rest_api_init', array( $this, 'register_rest_api_fields' ) );
        
        // Add additional information section to checkout (before payment section)
        // add_action( 'woocommerce_review_order_before_payment', array( $this, 'add_additional_info_section' ) );
        // add_action( 'woocommerce_checkout_process', array( $this, 'validate_additional_info_fields' ) );
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_additional_info_fields' ), 20, 2 );
    }

    /**
     * Add additional information section after billing form
     */
    public function add_additional_info_section() {
        $checkout = WC()->checkout();
        ?>
        <div class="woocommerce-additional-info-fields">
            <h3><?php esc_html_e( 'Aanvullende informatie', 'multistep-checkout' ); ?></h3>
            
            <?php
            woocommerce_form_field( 'msc_bank_account_number', array(
                'type'        => 'text',
                'class'       => array( 'form-row-wide' ),
                'label'       => __( 'Bankrekeningnummer (IBAN)', 'multistep-checkout' ),
                'placeholder' => __( 'NL00 BANK 0000 0000 00', 'multistep-checkout' ),
                'required'    => true,
            ), $checkout->get_value( 'msc_bank_account_number' ) );
            
            woocommerce_form_field( 'msc_direct_debit_agreement', array(
                'type'        => 'checkbox',
                'class'       => array( 'form-row-wide' ),
                'label'       => __( 'Ik ga akkoord met automatische incasso', 'multistep-checkout' ),
                'required'    => true,
            ), $checkout->get_value( 'msc_direct_debit_agreement' ) );
            
            woocommerce_form_field( 'msc_has_comments', array(
                'type'        => 'checkbox',
                'class'       => array( 'form-row-wide', 'msc-toggle-comments' ),
                'label'       => __( 'Ik heb opmerkingen', 'multistep-checkout' ),
                'required'    => false,
            ), $checkout->get_value( 'msc_has_comments' ) );
            ?>
            <div class="msc-comments-field" style="display: none;">
                <?php
                woocommerce_form_field( 'msc_comments', array(
                    'type'        => 'textarea',
                    'class'       => array( 'form-row-wide' ),
                    'label'       => __( 'Opmerkingen', 'multistep-checkout' ),
                    'placeholder' => __( 'Uw opmerkingen...', 'multistep-checkout' ),
                    'required'    => false,
                ), $checkout->get_value( 'msc_comments' ) );
                ?>
            </div>
            <?php
            $privacy_page_id = get_option( 'wp_page_for_privacy_policy' );
            $privacy_link = $privacy_page_id ? '<a href="' . esc_url( get_permalink( $privacy_page_id ) ) . '" target="_blank">' . __( 'privacystatement', 'multistep-checkout' ) . '</a>' : __( 'privacystatement', 'multistep-checkout' );
            
            woocommerce_form_field( 'msc_privacy_agreement', array(
                'type'        => 'checkbox',
                'class'       => array( 'form-row-wide' ),
                'label'       => sprintf( __( 'Ik ga akkoord met het %s', 'multistep-checkout' ), $privacy_link ),
                'required'    => true,
            ), $checkout->get_value( 'msc_privacy_agreement' ) );
            ?>
        </div>
        
        <script type="text/javascript">
            (function() {
                document.addEventListener('DOMContentLoaded', function() {
                    var commentsToggle = document.getElementById('msc_has_comments');
                    var commentsField = document.querySelector('.msc-comments-field');
                    if (commentsToggle && commentsField) {
                        commentsToggle.addEventListener('change', function() {
                            commentsField.style.display = this.checked ? 'block' : 'none';
                        });
                        if (commentsToggle.checked) {
                            commentsField.style.display = 'block';
                        }
                    }
                });
            })();
        </script>
        <?php
    }

    /**
     * Validate additional info fields
     */
    public function validate_additional_info_fields() {
        if ( empty( $_POST['msc_bank_account_number'] ) ) {
            wc_add_notice( __( 'Bankrekeningnummer is verplicht.', 'multistep-checkout' ), 'error' );
        }
        if ( empty( $_POST['msc_direct_debit_agreement'] ) ) {
            wc_add_notice( __( 'U dient akkoord te gaan met automatische incasso.', 'multistep-checkout' ), 'error' );
        }
        if ( empty( $_POST['msc_privacy_agreement'] ) ) {
            wc_add_notice( __( 'U dient akkoord te gaan met het privacystatement.', 'multistep-checkout' ), 'error' );
        }
    }

    /**
     * Save additional info fields to order
     */
    public function save_additional_info_fields( $order_id, $data ) {
        if ( ! empty( $_POST['msc_bank_account_number'] ) ) {
            update_post_meta( $order_id, '_msc_bank_account_number', sanitize_text_field( $_POST['msc_bank_account_number'] ) );
        }
        if ( ! empty( $_POST['msc_direct_debit_agreement'] ) ) {
            update_post_meta( $order_id, '_msc_direct_debit_agreement', 'yes' );
        }
        if ( ! empty( $_POST['msc_has_comments'] ) ) {
            update_post_meta( $order_id, '_msc_has_comments', 'yes' );
        }
        if ( ! empty( $_POST['msc_comments'] ) ) {
            update_post_meta( $order_id, '_msc_comments', sanitize_textarea_field( $_POST['msc_comments'] ) );
        }
        if ( ! empty( $_POST['msc_privacy_agreement'] ) ) {
            update_post_meta( $order_id, '_msc_privacy_agreement', 'yes' );
        }
    }

    /**
     * Remove submit button from Formidable form
     */
    public function remove_submit_button( $button, $form ) {
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
        $form_ids   = array();

        foreach ( $cart_items as $cart_item ) {
            if ( isset( $cart_item['product_id'] ) ) {
                $product_form_ids = get_post_meta( $cart_item['product_id'], '_msc_custom_form_ids', true );

                if ( empty( $product_form_ids ) ) {
                    $product_form_ids = get_post_meta( $cart_item['product_id'], '_msc_custom_form_id', true );
                    if ( ! empty( $product_form_ids ) ) {
                        $product_form_ids = array( $product_form_ids );
                    }
                }

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
                $form      = \FrmForm::getOne( $form_id );
                $form_name = $form ? $form->name : esc_html__( 'Additional Information', 'multistep-checkout' );

                $steps[] = array(
                    'id'    => 'opc-custom-form-' . $form_id,
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

                add_filter( 'frm_submit_button_html',  array( $this, 'remove_submit_button' ),        99, 2 );
                add_filter( 'frm_submit_button_class', array( $this, 'remove_submit_button_classes' ), 99, 2 );
                add_filter( 'frm_include_form_tag',    array( $this, 'remove_form_tag' ),              99, 2 );
                ?>
                <div id="checkout-step-custom-form-<?php echo esc_attr( $form_id ); ?>" class="a-item" style="display: none;">
                    <div class="custom-form-wrapper">
                        <?php echo do_shortcode( '[formidable id="' . esc_attr( $form_id ) . '"]' ); ?>
                    </div>
                    <button type="button" class="button prev-btn"><span><?php esc_html_e( 'Vorige', 'multistep-checkout' ); ?></span></button>
                    <button type="button" class="button next-btn"><span><?php esc_html_e( 'Volgende', 'multistep-checkout' ); ?></span></button>
                </div>
                <?php
                remove_filter( 'frm_submit_button_html',  array( $this, 'remove_submit_button' ),        99 );
                remove_filter( 'frm_submit_button_class', array( $this, 'remove_submit_button_classes' ), 99 );
                remove_filter( 'frm_include_form_tag',    array( $this, 'remove_form_tag' ),              99 );

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

        foreach ( $form_ids as $form_id ) {
            if ( ! Form_Validator::should_display_form( $form_id ) ) {
                continue;
            }

            $validation = Form_Validator::validate_form_submission( $form_id, $_POST );

            if ( ! $validation['valid'] && ! empty( $validation['errors'] ) ) {
                foreach ( $validation['errors'] as $error ) {
                    wc_add_notice( $error, 'error' );
                }
            }
        }
    }

    /**
     * Save Formidable form data to order meta.
     *
     * Repeater fields (type: repeat / form) are FLATTENED into individual
     * entries so that every template — thank-you page, invoice PDF, order
     * e-mail — can render them without modification.  Each sub-field is
     * stored as a regular field entry where:
     *
     *   field_label  => "#1 - Naam"          (row number + sub-field name)
     *   value        => "Jan"                 (always a plain string)
     *   field_row    => 1                     (row index, 1-based)
     *   field_section=> "Alarmopvolgers"      (parent repeater field name)
     *
     * Templates that only read field_label / value continue to work as-is.
     * The admin meta-box uses field_row / field_section to add visual grouping.
     */
    public function save_formidable_data_to_order( $order_id, $data ) {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return;
        }

        $cart_items    = WC()->cart->get_cart();
        $all_form_data = array();

        foreach ( $cart_items as $cart_item_key => $cart_item ) {
            if ( ! isset( $cart_item['product_id'] ) ) {
                continue;
            }

            $product_id = $cart_item['product_id'];

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

            $product      = wc_get_product( $product_id );
            $product_name = $product ? $product->get_name() : __( 'Unknown Product', 'multistep-checkout' );

            foreach ( $form_ids as $form_id ) {
                $form = \FrmForm::getOne( $form_id );
                if ( ! $form ) {
                    continue;
                }

                $fields    = \FrmField::get_all_for_form( $form_id );
                $form_data = array(
                    'form_id'      => $form_id,
                    'form_name'    => $form->name,
                    'product_id'   => $product_id,
                    'product_name' => $product_name,
                    'fields'       => array(),
                );

                // Types that carry no user-entered value
                $skip_types = array( 'divider', 'html', 'break', 'captcha', 'end_divider' );

                foreach ( $fields as $field ) {

                    if ( in_array( $field->type, $skip_types ) ) {
                        continue;
                    }

                    // ── REPEATER FIELD ────────────────────────────────────────────────
                    if ( in_array( $field->type, array( 'repeat', 'form' ) ) ) {
                        $embedded_form_id = isset( $field->field_options['form_select'] )
                            ? absint( $field->field_options['form_select'] )
                            : 0;

                        if ( ! $embedded_form_id ) {
                            continue;
                        }

                        $sub_fields = \FrmField::get_all_for_form( $embedded_form_id );

                        // Count submitted rows
                        $row_count = 0;
                        foreach ( $sub_fields as $sf ) {
                            if ( isset( $_POST['item_meta'][ $sf->id ] ) && is_array( $_POST['item_meta'][ $sf->id ] ) ) {
                                $row_count = max( $row_count, count( $_POST['item_meta'][ $sf->id ] ) );
                            }
                        }

                        // Flatten each row into individual field entries
                        for ( $row = 0; $row < $row_count; $row++ ) {
                            foreach ( $sub_fields as $sf ) {
                                if ( in_array( $sf->type, array_merge( $skip_types, array( 'repeat', 'form' ) ) ) ) {
                                    continue;
                                }

                                $sub_value = null;

                                if ( isset( $_POST['item_meta'][ $sf->id ] ) ) {
                                    $raw = $_POST['item_meta'][ $sf->id ];
                                    if ( is_array( $raw ) && array_key_exists( $row, $raw ) ) {
                                        $sub_value = $raw[ $row ];
                                    } elseif ( ! is_array( $raw ) ) {
                                        $sub_value = $raw;
                                    }
                                }

                                if ( $sub_value === null || $sub_value === '' ) {
                                    continue;
                                }

                                // Normalise to string
                                if ( is_array( $sub_value ) ) {
                                    // Associative (name/address compound) → join parts
                                    if ( array_keys( $sub_value ) !== range( 0, count( $sub_value ) - 1 ) ) {
                                        $sub_value = implode( ' ', array_filter( array_map( 'sanitize_text_field', $sub_value ) ) );
                                    } else {
                                        // Sequential (checkbox / multi-select)
                                        $sub_value = implode( ', ', array_map( 'sanitize_text_field', $sub_value ) );
                                    }
                                } else {
                                    $sub_value = sanitize_text_field( $sub_value );
                                }

                                if ( $sub_value === '' ) {
                                    continue;
                                }

                                /*
                                 * Store as a FLAT entry so every template works without changes.
                                 *
                                 * field_label  — human-readable label used by all templates
                                 * field_row    — used only by the admin meta-box for visual grouping
                                 * field_section— used only by the admin meta-box for group header
                                 */
                                $form_data['fields'][] = array(
                                    'field_id'      => $sf->id,
                                    'field_key'     => $sf->field_key,
                                    'field_name'    => $sf->name,
                                    'field_label'   => '#' . ( $row + 1 ) . ' - ' . $sf->name,
                                    'field_type'    => $sf->type,
                                    'field_row'     => $row + 1,
                                    'field_section' => $field->name,
                                    'value'         => $sub_value,
                                );
                            }
                        }

                        continue; // move on to next top-level field
                    }
                    // ── END REPEATER ──────────────────────────────────────────────────

                    // Regular field
                    $value = null;

                    if ( isset( $_POST['item_meta'] ) && is_array( $_POST['item_meta'] ) ) {
                        if ( isset( $_POST['item_meta'][ $field->id ] ) ) {
                            $value = $_POST['item_meta'][ $field->id ];
                        }
                    }

                    if ( $value === null || $value === '' ) {
                        continue;
                    }

                    if ( is_array( $value ) ) {
                        if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
                            $value = implode( ' ', array_filter( array_map( 'sanitize_text_field', $value ) ) );
                        } else {
                            $value = implode( ', ', array_map( 'sanitize_text_field', $value ) );
                        }
                    } else {
                        $value = sanitize_text_field( $value );
                    }

                    if ( $value === '' ) {
                        continue;
                    }

                    $form_data['fields'][] = array(
                        'field_id'    => $field->id,
                        'field_key'   => $field->field_key,
                        'field_name'  => $field->name,
                        'field_label' => $field->name,
                        'field_type'  => $field->type,
                        'value'       => $value,
                    );
                }

                if ( ! empty( $form_data['fields'] ) ) {
                    $all_form_data[] = $form_data;
                }
            }
        }

        if ( ! empty( $all_form_data ) ) {
            update_post_meta( $order_id, '_formidable_forms_data', $all_form_data );
        }
    }

    /**
     * Populate WooCommerce billing and shipping fields from Formidable form data.
     * Maps the "Abonnee" form fields to billing address fields.
     */
    public function populate_billing_from_formidable( $order_id, $data ) {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return;
        }

        $form_data = get_post_meta( $order_id, '_formidable_forms_data', true );

        if ( empty( $form_data ) || ! is_array( $form_data ) ) {
            return;
        }

        $abonnee_form = null;
        foreach ( $form_data as $form ) {
            if ( stripos( $form['form_name'], 'Abonnee' ) !== false ) {
                $abonnee_form = $form;
                break;
            }
        }

        if ( empty( $abonnee_form ) || empty( $abonnee_form['fields'] ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Name
        $first_name = '';
        $last_name  = '';
        foreach ( $abonnee_form['fields'] as $field ) {
            if ( $field['field_type'] === 'name' ) {
                $name_value = $field['value'];
                $decoded    = json_decode( $name_value, true );
                if ( is_array( $decoded ) ) {
                    $first_name = isset( $decoded['first'] ) ? $decoded['first'] : '';
                    $last_name  = isset( $decoded['last'] )  ? $decoded['last']  : '';
                } else {
                    $parts      = explode( ' ', trim( $name_value ), 2 );
                    $first_name = isset( $parts[0] ) ? $parts[0] : '';
                    $last_name  = isset( $parts[1] ) ? $parts[1] : '';
                }
                break;
            }
        }
        if ( ! empty( $first_name ) ) $order->set_billing_first_name( $first_name );
        if ( ! empty( $last_name ) )  $order->set_billing_last_name( $last_name );

        // Email
        foreach ( $abonnee_form['fields'] as $field ) {
            if ( $field['field_type'] === 'email' ) {
                $order->set_billing_email( $field['value'] );
                break;
            }
        }

        // Phone (prefer mobile)
        $phone_value = '';
        foreach ( $abonnee_form['fields'] as $field ) {
            if ( $field['field_type'] === 'phone' ) {
                if ( stripos( $field['field_name'], 'mobiel' ) !== false ) {
                    $phone_value = $field['value'];
                    break;
                }
                if ( empty( $phone_value ) ) {
                    $phone_value = $field['value'];
                }
            }
        }
        if ( ! empty( $phone_value ) ) $order->set_billing_phone( $phone_value );

        // Address
        foreach ( $abonnee_form['fields'] as $field ) {
            if ( $field['field_type'] === 'address' ) {
                $address_data  = $field['value'];
                $address_array = array();
                if ( is_string( $address_data ) && strpos( $address_data, '{' ) === 0 ) {
                    $address_array = json_decode( $address_data, true );
                }
                $street   = isset( $address_array['line1'] )   ? $address_array['line1']   : '';
                $number   = isset( $address_array['line2'] )   ? $address_array['line2']   : '';
                $city     = isset( $address_array['city'] )    ? $address_array['city']    : '';
                $postcode = isset( $address_array['zip'] )     ? $address_array['zip']     : '';
                $country  = isset( $address_array['country'] ) ? $address_array['country'] : '';
                if ( ! empty( $street ) ) {
                    if ( ! empty( $number ) ) $street .= ' ' . $number;
                    $order->set_billing_address_1( $street );
                }
                if ( ! empty( $city ) )     $order->set_billing_city( $city );
                if ( ! empty( $postcode ) ) $order->set_billing_postcode( $postcode );
                if ( ! empty( $country ) )  $order->set_billing_country( $country );
                break;
            }
        }

        // Mirror to shipping
        if ( $order->get_billing_first_name() ) $order->set_shipping_first_name( $order->get_billing_first_name() );
        if ( $order->get_billing_last_name() )  $order->set_shipping_last_name( $order->get_billing_last_name() );
        if ( $order->get_billing_address_1() )  $order->set_shipping_address_1( $order->get_billing_address_1() );
        if ( $order->get_billing_city() )       $order->set_shipping_city( $order->get_billing_city() );
        if ( $order->get_billing_postcode() )   $order->set_shipping_postcode( $order->get_billing_postcode() );
        if ( $order->get_billing_country() )    $order->set_shipping_country( $order->get_billing_country() );

        $order->save();
    }

    /**
     * Add meta box for form information
     */
    public function add_form_info_meta_box() {
        $args = array(
            'id'       => 'msc_form_information',
            'title'    => esc_html__( 'Form Information', 'multistep-checkout' ),
            'callback' => array( $this, 'display_formidable_data_in_admin' ),
            'context'  => 'normal',
            'priority' => 'default',
        );

        add_meta_box( $args['id'], $args['title'], $args['callback'], 'shop_order',                    $args['context'], $args['priority'] );
        add_meta_box( $args['id'], $args['title'], $args['callback'], 'woocommerce_page_wc-orders',    $args['context'], $args['priority'] );
    }

    /**
     * Display Formidable form data in admin order page.
     *
     * Flat repeater entries (identified by the presence of field_row /
     * field_section keys) are grouped under a coloured section header so the
     * admin view stays readable.  All other templates (thank-you, invoice,
     * e-mail) receive plain string values and need no modification.
     */
    public function display_formidable_data_in_admin( $post_or_order ) {
        if ( is_a( $post_or_order, 'WP_Post' ) ) {
            $order_id = $post_or_order->ID;
        } elseif ( is_a( $post_or_order, 'WC_Order' ) ) {
            $order_id = $post_or_order->get_id();
        } else {
            return;
        }

        $form_data         = get_post_meta( $order_id, '_formidable_forms_data', true );
        $bank_account      = get_post_meta( $order_id, '_msc_bank_account_number', true );
        $direct_debit      = get_post_meta( $order_id, '_msc_direct_debit_agreement', true );
        $has_comments      = get_post_meta( $order_id, '_msc_has_comments', true );
        $comments          = get_post_meta( $order_id, '_msc_comments', true );
        $privacy_agreement = get_post_meta( $order_id, '_msc_privacy_agreement', true );

        // --- Aanvullende informatie block ---
        if ( $bank_account || $direct_debit || $privacy_agreement ) {
            echo '<div style="margin-bottom:20px;padding:15px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;">';
            echo '<div style="margin-bottom:15px;padding-bottom:10px;border-bottom:2px solid #2271b1;"><h4 style="margin:0;">' . esc_html__( 'Aanvullende informatie', 'multistep-checkout' ) . '</h4></div>';
            echo '<table class="widefat striped" style="margin-top:0;background:white;"><thead><tr>';
            echo '<th style="width:35%;padding:10px;">' . esc_html__( 'Field', 'multistep-checkout' ) . '</th>';
            echo '<th style="padding:10px;">'           . esc_html__( 'Value', 'multistep-checkout' ) . '</th>';
            echo '</tr></thead><tbody>';

            if ( $bank_account ) {
                echo '<tr><td style="font-weight:600;padding:10px;">' . esc_html__( 'Bankrekeningnummer (IBAN)', 'multistep-checkout' ) . '</td><td style="padding:10px;">' . esc_html( $bank_account ) . '</td></tr>';
            }
            echo '<tr><td style="font-weight:600;padding:10px;">' . esc_html__( 'Akkoord automatische incasso', 'multistep-checkout' ) . '</td><td style="padding:10px;">' . ( $direct_debit === 'yes' ? '<span style="color:green;">&#10003; Ja</span>' : '<span style="color:red;">&#10007; Nee</span>' ) . '</td></tr>';

            if ( $has_comments === 'yes' && $comments ) {
                echo '<tr><td style="font-weight:600;padding:10px;">' . esc_html__( 'Opmerkingen', 'multistep-checkout' ) . '</td><td style="padding:10px;">' . wp_kses_post( nl2br( esc_html( $comments ) ) ) . '</td></tr>';
            }
            echo '<tr><td style="font-weight:600;padding:10px;">' . esc_html__( 'Akkoord privacystatement', 'multistep-checkout' ) . '</td><td style="padding:10px;">' . ( $privacy_agreement === 'yes' ? '<span style="color:green;">&#10003; Ja</span>' : '<span style="color:red;">&#10007; Nee</span>' ) . '</td></tr>';

            echo '</tbody></table></div>';
        }

        if ( empty( $form_data ) ) {
            if ( ! $bank_account && ! $direct_debit && ! $privacy_agreement ) {
                echo '<p style="color:#666;">' . esc_html__( 'No form data available for this order.', 'multistep-checkout' ) . '</p>';
            }
            return;
        }

        foreach ( $form_data as $form ) {
            echo '<div style="margin-bottom:20px;padding:15px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;">';
            echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;padding-bottom:10px;border-bottom:2px solid #2271b1;">';
            echo '<h4 style="margin:0;">' . esc_html( $form['form_name'] ) . '</h4>';
            if ( ! empty( $form['product_name'] ) ) {
                echo '<span style="background:#2271b1;color:white;padding:5px 12px;border-radius:3px;font-size:12px;font-weight:600;">' . esc_html( $form['product_name'] ) . '</span>';
            }
            echo '</div>';

            if ( empty( $form['fields'] ) ) {
                echo '<p style="color:#d63638;margin:0;">' . esc_html__( 'No fields captured.', 'multistep-checkout' ) . '</p>';
            } else {
                echo '<table class="widefat striped" style="margin-top:0;background:white;"><thead><tr>';
                echo '<th style="width:35%;padding:10px;">' . esc_html__( 'Field', 'multistep-checkout' ) . '</th>';
                echo '<th style="padding:10px;">'           . esc_html__( 'Value', 'multistep-checkout' ) . '</th>';
                echo '</tr></thead><tbody>';

                $current_section_row = null; // tracks "SectionName|RowNumber" for grouping headers

                foreach ( $form['fields'] as $field ) {
                    $is_repeater = isset( $field['field_row'] ) && isset( $field['field_section'] );

                    if ( $is_repeater ) {
                        $section_key = $field['field_section'] . '|' . $field['field_row'];

                        // Print section header when we enter a new group
                        if ( $current_section_row !== $section_key ) {
                            $current_section_row = $section_key;
                            echo '<tr>';
                            echo '<td colspan="2" style="font-weight:700;padding:8px 10px;background:#e8f0fb;border-top:2px solid #c3d4f0;">';
                            echo esc_html( $field['field_section'] ) . ' #' . esc_html( $field['field_row'] );
                            echo '</td></tr>';
                        }

                        // Sub-field row with indent
                        echo '<tr>';
                        echo '<td style="font-weight:600;padding:8px 10px 8px 30px;color:#555;">&#8627; ' . esc_html( $field['field_name'] ) . '</td>';
                        echo '<td style="padding:8px 10px;">' . wp_kses_post( nl2br( esc_html( $field['value'] ) ) ) . '</td>';
                        echo '</tr>';
                    } else {
                        $current_section_row = null; // reset grouping when back to regular fields
                        echo '<tr>';
                        echo '<td style="font-weight:600;padding:10px;">' . esc_html( $field['field_label'] ) . '</td>';
                        echo '<td style="padding:10px;">'                 . wp_kses_post( nl2br( esc_html( $field['value'] ) ) ) . '</td>';
                        echo '</tr>';
                    }
                }

                echo '</tbody></table>';
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
                'get_callback'    => array( $this, 'get_form_data_for_api' ),
                'update_callback' => null,
                'schema'          => array(
                    'description' => __( 'Formidable Forms data submitted with the order', 'multistep-checkout' ),
                    'type'        => 'array',
                    'context'     => array( 'view', 'edit' ),
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'form_id'      => array( 'type' => 'integer', 'description' => __( 'Formidable Form ID', 'multistep-checkout' ) ),
                            'form_name'    => array( 'type' => 'string',  'description' => __( 'Formidable Form Name', 'multistep-checkout' ) ),
                            'product_id'   => array( 'type' => 'integer', 'description' => __( 'Product ID', 'multistep-checkout' ) ),
                            'product_name' => array( 'type' => 'string',  'description' => __( 'Product Name', 'multistep-checkout' ) ),
                            'fields'       => array(
                                'type'        => 'array',
                                'description' => __( 'Form fields and values', 'multistep-checkout' ),
                                'items'       => array(
                                    'type'       => 'object',
                                    'properties' => array(
                                        'field_id'      => array( 'type' => 'integer' ),
                                        'field_key'     => array( 'type' => 'string' ),
                                        'field_name'    => array( 'type' => 'string' ),
                                        'field_label'   => array( 'type' => 'string' ),
                                        'field_type'    => array( 'type' => 'string' ),
                                        'field_row'     => array( 'type' => 'integer' ),
                                        'field_section' => array( 'type' => 'string' ),
                                        'value'         => array( 'type' => 'string' ),
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
        $order_id  = $object['id'];
        $form_data = get_post_meta( $order_id, '_formidable_forms_data', true );

        if ( empty( $form_data ) || ! is_array( $form_data ) ) {
            return array();
        }

        return $form_data;
    }
}