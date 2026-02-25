<?php

/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

if (! defined('ABSPATH')) {
	exit;
}

// print notices.
wc_print_notices();

// If checkout registration is disabled and not logged in, the user cannot checkout.
if (! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in()) {
	echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'multistep-checkout')));
	return;
}
?>

<?php
// Build default steps
$checkout_steps = array();

// Only show login step if user is not logged in AND guest checkout is not allowed
$guest_checkout_enabled = 'yes' === get_option('woocommerce_enable_guest_checkout');
$registration_required = ! $guest_checkout_enabled;

if (! is_user_logged_in() && $registration_required) {
	$checkout_steps[] = array(
		'id' => 'opc-login',
		'title' => esc_html__('Login', 'multistep-checkout'),
	);
}

// Apply filter to allow custom steps to be added (custom forms will be added here)
$checkout_steps = apply_filters('msc_checkout_steps', $checkout_steps);

if (wc_coupons_enabled()) {
	$checkout_steps[] = array(
		'id' => 'opc-coupon',
		'title' => esc_html__('Coupon', 'multistep-checkout'),
	);
}

$checkout_steps[] = array(
	'id' => 'opc-checkout',
	'title' => esc_html__('Checkout', 'multistep-checkout'),
);
?>

<div class="multistep-checkout-wrapper">
	<div class="checkout-step-section-title">
		<h1><?php esc_html_e('Rond uw bestelling af', 'multistep-checkout'); ?></h1>
	</div>
	<ol class="one-page-checkout" id="checkoutSteps">
		<?php foreach ($checkout_steps as $step) : ?>
			<li id="<?php echo esc_attr($step['id']); ?>" class="step">
				<div class="step-title">
					<span class="number">#</span>
					<h3><?php echo esc_html($step['title']); ?></h3>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>

	<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">

		<?php
		// Only show login step if guest checkout is not allowed
		if (! is_user_logged_in() && $registration_required) {
		?>
			<div id="checkout-step-login" class="a-item" style="display: none;">
				<?php include MSC_PATH . '/woocommerce/checkout/form-login.php'; ?>
				<button type="button" class="button prev-btn"><span><?php esc_html_e('Previous', 'multistep-checkout'); ?></span></button>
				<button type="button" class="button next-btn"><span><?php esc_html_e('Continue', 'multistep-checkout'); ?></span></button>
			</div>
		<?php } ?>

		<?php
		// Apply filter to add custom form content (CUSTOM FORMS APPEAR FIRST)
		$custom_content = apply_filters('msc_checkout_content', array());
		if (! empty($custom_content)) {
			foreach ($custom_content as $content) {
				echo $content;
			}
		}
		?>

		<?php if (wc_coupons_enabled()) { ?>
			<div id="checkout-step-coupon" class="a-item" style="display: none;">
				<?php include MSC_PATH . '/woocommerce/checkout/form-coupon.php'; ?>
				<button type="button" class="button prev-btn"><span><?php esc_html_e('Previous', 'multistep-checkout'); ?></span></button>
				<button type="button" class="button next-btn"><span><?php esc_html_e('Continue', 'multistep-checkout'); ?></span></button>
			</div>
		<?php } ?>

		<?php if ($checkout->get_checkout_fields()) : ?>
			<?php do_action('woocommerce_checkout_before_customer_details'); ?>

			<div id="checkout-step-checkout" class="a-item" style="display: none;">
				<div class="grid grid-cols-1 xl:grid-cols-12 lg:grid-cols-6 gap-8">
					<!-- Left Column: Form Summary -->
					<div class="xl:col-span-7 lg:col-span-6 checkout-form-summary">
						<h3 class="text-xl font-bold mb-4"><?php esc_html_e('Your Information', 'multistep-checkout'); ?></h3>
						<div class="form-summary-content">
							<?php
							// Hook to display summary of previous form steps
							do_action('msc_checkout_form_summary');

							include __DIR__ . '/dummy-filled-form.php';
							?>
						</div>
						<div class="checkout-combined">
							<?php do_action('woocommerce_checkout_billing'); ?>
							<?php do_action('woocommerce_checkout_shipping'); ?>

							<?php do_action('woocommerce_checkout_after_order_review'); ?>
						</div>
					</div>

					<!-- Right Column: Shopping Cart & Order Review -->
					<div class="xl:col-span-5 lg:col-span-6 checkout-order-review">
						<?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
						<div id="order_review" class="woocommerce-checkout-review-order">
							<?php do_action('woocommerce_checkout_order_review'); ?>
						</div>
					</div>
				</div>
				<button type="button" class="button prev-btn"><span><?php esc_html_e('Previous', 'multistep-checkout'); ?></span></button>
			</div>

			<?php do_action('woocommerce_checkout_after_customer_details'); ?>
		<?php endif; ?>

	</form>
</div>
<?php do_action('woocommerce_after_checkout_form', $checkout); ?>