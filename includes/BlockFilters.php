<?php
/**
 * Block Filters
 *
 * @package MultiStep_Checkout
 */

namespace MultiStep_Checkout;

defined( 'ABSPATH' ) || exit;

/**
 * Class Block_Filters
 */
class Block_Filters {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'allowed_block_types_all', array( $this, 'add_woocommerce_blocks' ), 10, 2 );
	}

	/**
	 * Add WooCommerce Classic Checkout block to allowed blocks
	 *
	 * @param array $allowed_blocks Array of allowed blocks.
	 * @param object $editor_context Editor context.
	 * @return array
	 */
	public function add_woocommerce_blocks( $allowed_blocks, $editor_context ) {
		// If allowed_blocks is not an array, make it one
		if ( ! is_array( $allowed_blocks ) ) {
			$allowed_blocks = array();
		}

		// Add WooCommerce Classic Checkout block
		$allowed_blocks[] = 'woocommerce/classic-shortcode';

		return $allowed_blocks;
	}
}
