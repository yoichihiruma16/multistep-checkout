<?php
/**
 * Plugin Name: MultiStep Checkout
 * Plugin URI:  https://wpxon.com/plugins/multistep-checkout
 * Description: A MultiStep Checkout plugin for WooCommerce.
 * Author:      WPxon
 * Author URI:  https://wpxon.com
 * Version:     1.0.2
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tags: multistep checkout, multi step checkout, woocommerce multistep checkout,  woocommerce multi step checkout, WooCommerce checkout steps
 * Text Domain: multistep-checkout
 * Requires Plugins: woocommerce, formidable, advanced-custom-fields-pro
 */

defined( 'ABSPATH' ) || exit;

// Check for required plugins
add_action( 'admin_init', 'msc_check_required_plugins' );
add_action( 'admin_notices', 'msc_required_plugins_notice' );

/**
 * Check if required plugins are active
 */
function msc_check_required_plugins() {
	if ( is_plugin_active( 'multistep-checkout/multistep-checkout.php' ) ) {
		$required_plugins = msc_get_missing_plugins();
		
		if ( ! empty( $required_plugins ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			add_action( 'admin_notices', 'msc_required_plugins_notice' );
		}
	}
}

/**
 * Get list of missing required plugins
 */
function msc_get_missing_plugins() {
	$missing = array();
	
	// Check WooCommerce
	if ( ! class_exists( 'WooCommerce' ) ) {
		$missing[] = 'WooCommerce';
	}
	
	// Check Formidable Forms Pro
	if ( ! class_exists( 'FrmAppHelper' ) ) {
		$missing[] = 'Formidable Forms Pro';
	}
	
	// Check Advanced Custom Fields Pro
	if ( ! class_exists( 'ACF' ) ) {
		$missing[] = 'Advanced Custom Fields Pro';
	}
	
	return $missing;
}

/**
 * Display admin notice for missing required plugins
 */
function msc_required_plugins_notice() {
	$missing_plugins = msc_get_missing_plugins();
	
	if ( ! empty( $missing_plugins ) ) {
		$plugin_list = implode( ', ', $missing_plugins );
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'MultiStep Checkout', 'multistep-checkout' ); ?></strong> 
				<?php esc_html_e( 'requires the following plugins to be installed and activated:', 'multistep-checkout' ); ?>
			</p>
			<ul style="list-style: disc; margin-left: 20px;">
				<?php foreach ( $missing_plugins as $plugin ) : ?>
					<li><?php echo esc_html( $plugin ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p><?php esc_html_e( 'The plugin has been deactivated. Please install and activate the required plugins, then try again.', 'multistep-checkout' ); ?></p>
		</div>
		<?php
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/Form_Validator.php';
require_once __DIR__ . '/includes/FormSummary.php';
require_once __DIR__ . '/includes/BlockFilters.php';

/**
 * The main plugin class
 */
final class MultiStep_Checkout {

	/**
	 * plugin version
	 *
	 * @var string
	 */
	const version = '1.0.2';

	/**
	 * Class constructor
	 */
	function __construct() {
		$this->define_constants();
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
		add_filter( 'woocommerce_locate_template', array( $this, 'load_checkout_template' ), 10, 3 );

	}

	/**
	 * Initializes a singletone instance
	 *
	 * @return \MultiStep_Checkout
	 */
	public static function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Define the required plugin constants
	 *
	 * @return void
	 */
	public function define_constants() {
		define( 'MSC_VERSION', self::version );
		define( 'MSC_FILE', __FILE__ );
		define( 'MSC_PATH', __DIR__ );
		define( 'MSC_URL', plugins_url( '', MSC_FILE ) );
		define( 'MSC_ASSETS', MSC_URL . '/assets' );
	}


	public function init_plugin() {
		// Check if required plugins are active before initializing
		if ( ! empty( msc_get_missing_plugins() ) ) {
			return;
		}

		$this->woo_actions();

		new MultiStep_Checkout\Assets();
		new MultiStep_Checkout\Checkout_Form();
		new MultiStep_Checkout\Form_Summary();
		new MultiStep_Checkout\Block_Filters();

		if ( is_admin() ) {
			new MultiStep_Checkout\Admin();
		}
	}

	/**
	 * Do stuff uplon plugin activation
	 *
	 * @return void
	 */
	public function activate() {
		// save version.
		$installed = get_option( 'msc_installed' );
		if ( ! $installed ) {
			update_option( 'msc_installed', time() );
		}
		update_option( 'msc_version', MSC_VERSION ); 
	}

	/**
	 * Load checkout template
	 */
	public function load_checkout_template( $template, $template_name, $template_path ) {

		if ( 'checkout/form-checkout.php' == $template_name ) {

			$template = MSC_PATH . '/woocommerce/checkout/form-checkout.php';

		}

		return $template;
	}

	public function woo_actions() {
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		add_action( 'woocommerce_checkout_after_order_review', 'woocommerce_checkout_payment', 20 );
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		
		// Enable guest checkout
		add_filter( 'woocommerce_enable_guest_checkout', '__return_true' );
		add_filter( 'woocommerce_checkout_registration_required', '__return_false' );
	}
}

/**
 * Initializes the main plugin
 *
 * @return \MultiStep_Checkout
 */
function multistep_checkout() {
	return MultiStep_Checkout::init();
}

// kick-off the plugin
multistep_checkout();
