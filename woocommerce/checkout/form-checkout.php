<?php
/**
 * Checkout form template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you (the theme developer) 
 * will need to copy the new files to your theme to maintain compatibility.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! WC()->cart->is_empty() ) :
    
    // Start the checkout form
    echo '<div class="checkout-container">';
    echo '    <div class="cart-review">';
    echo '        <h2>' . esc_html__( 'Shopping Cart', 'woocommerce' ) . '</h2>';
    echo '        <div class="cart-items">';
    wc_cart_content(); // Function to display the cart contents
    echo '        </div>';
    echo '    </div>';
    echo '    <div class="order-review">';
    echo '        <h2>' . esc_html__( 'Order Review', 'woocommerce' ) . '</h2>';
    echo '        <div class="order-details">';
    // Here you can place additional order review details such as payment details, etc.
    echo '        </div>';
    echo '    </div>';
    echo '</div>';
    
    echo '<form method="post" class="checkout" enctype="multipart/form-data">';
    
    echo '<div class="grid-layout">';
    // Add the checkout fields here
    do_action( 'woocommerce_checkout_billing' );
    do_action( 'woocommerce_checkout_shipping' );
    do_action( 'woocommerce_checkout_order_review' );
    echo '</div>';
    
    echo '</form>';
} else {
    echo '<p>' . esc_html__( 'Your cart is currently empty.', 'woocommerce' ) . '</p>';
}

do_action( 'woocommerce_after_checkout_form', $checkout );
