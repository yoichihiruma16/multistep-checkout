<?php
/**
 * Form Summary Handler
 *
 * @package MultiStep_Checkout
 */

namespace MultiStep_Checkout;

defined( 'ABSPATH' ) || exit;

/**
 * Class Form_Summary
 */
class Form_Summary {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'msc_checkout_form_summary', array( $this, 'display_form_summary' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Display summary of Formidable form data from previous steps
	 */
	public function display_form_summary() {
		// This will be populated by JavaScript
		echo '<div id="msc-form-summary" class="msc-form-summary"></div>';
	}

	/**
	 * Enqueue custom JavaScript for form data capture
	 */
	public function enqueue_scripts() {
		if ( ! is_checkout() ) {
			return;
		}

		wp_enqueue_script(
			'msc-form-summary',
			MSC_ASSETS . '/js/form-summary.js',
			array( 'jquery' ),
			MSC_VERSION,
			true
		);
	}
}
