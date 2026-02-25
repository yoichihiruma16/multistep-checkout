<?php 

namespace MultiStep_Checkout;

/**
 * The Assets Handler Class
 */
class Assets {
	
	function __construct() {

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] , 99 );
		add_action('wp_head',[ $this, 'custom_css'],99);

	}
	
	public function custom_css(){
		// Don't output custom CSS in wp-admin
		if ( is_admin() ) {
			return;
		}

		$color   = wpx_get_option('btn_text_color','wpx_styles','#333');
		$bgColor = wpx_get_option('btn_bg_color','wpx_styles','#fdd922');
		?>
		<style>
			.one-page-checkout .active .step-title{
				border-color: <?php echo esc_attr($bgColor); ?>;
			}
			.one-page-checkout .active .step-title .number{
				background-color: <?php echo esc_attr( $bgColor ); ?>;
				color: <?php echo esc_attr( $color ); ?> !important;
			}
			.one-page-checkout button{
				background-color: <?php echo esc_attr( $bgColor ); ?> !important;
				color: <?php echo esc_attr( $color ); ?> !important;
			}
			
			/* Checkout Step Navigation Buttons */
			.multistep-checkout-wrapper .a-item {
				margin-bottom: 20px;
			}
			
			.multistep-checkout-wrapper .button.prev-btn,
			.multistep-checkout-wrapper .button.next-btn {
				padding: 12px 30px;
				font-size: 16px;
				font-weight: 600;
				border: none;
				border-radius: 4px;
				cursor: pointer;
				transition: all 0.3s ease;
				text-transform: uppercase;
				letter-spacing: 0.5px;
				margin: 10px 5px;
				display: inline-block;
			}
			
			.multistep-checkout-wrapper .button.prev-btn {
				background-color: #f5f5f5 !important;
				color: #333 !important;
				border: 2px solid #ddd;
			}
			
			.multistep-checkout-wrapper .button.prev-btn:hover {
				background-color: #e0e0e0 !important;
				border-color: #bbb;
				transform: translateY(-2px);
				box-shadow: 0 4px 8px rgba(0,0,0,0.1);
			}
			
			.multistep-checkout-wrapper .button.next-btn {
				background-color: <?php echo esc_attr( $bgColor ); ?> !important;
				color: <?php echo esc_attr( $color ); ?> !important;
				border: 2px solid <?php echo esc_attr( $bgColor ); ?>;
			}
			
			.multistep-checkout-wrapper .button.next-btn:hover {
				opacity: 0.9;
				transform: translateY(-2px);
				box-shadow: 0 4px 12px rgba(0,0,0,0.15);
			}
			
			.multistep-checkout-wrapper .button.prev-btn:active,
			.multistep-checkout-wrapper .button.next-btn:active {
				transform: translateY(0);
				box-shadow: 0 2px 4px rgba(0,0,0,0.1);
			}
			
			/* Button Container Alignment */
			.multistep-checkout-wrapper .a-item {
				text-align: left;
			}
			
			@media (max-width: 768px) {
				.multistep-checkout-wrapper .button.prev-btn,
				.multistep-checkout-wrapper .button.next-btn {
					width: 100%;
					margin: 5px 0;
					padding: 15px 20px;
				}
			}
		</style>		
		<?php
	}

	public function get_scripts() {
		return [
			'multistep-checkout-scripts' => [
				'src'     => MSC_ASSETS . '/js/form-summary.js',
				'version' => filemtime( MSC_PATH . '/assets/js/form-summary.js' ), 
				'deps' => ['jquery'], 
			],
			'multistep-checkout-billing-populator' => [
				'src'     => MSC_ASSETS . '/js/populate-billing-form.js',
				'version' => filemtime( MSC_PATH . '/assets/js/populate-billing-form.js' ), 
				'deps' => ['jquery'], 
			]
		];
	}

	public function get_styles() {
		return [ 
			'multistep-checkout-style' => [
				'src'     => MSC_ASSETS . '/css/frontend.css',
				'version' => filemtime( MSC_PATH . '/assets/css/frontend.css' ),
			] 
		];
	}

	public function enqueue_assets() {
		// Don't load frontend assets in wp-admin
		if ( is_admin() ) {
			return;
		}

		// scripts.
		$scripts = $this->get_scripts();
		foreach ($scripts as $handler => $script) {
			$deps = isset( $script['deps'] ) ? $script['deps'] : false;	// dependencis	
			$load = isset( $script['load'] ) ? $script['load'] : true;	// load on header or footer
			wp_register_script( $handler, $script['src'], $deps, $script['version'], $load );
		}
 
		// styles
		$styles = $this->get_styles();
		foreach ($styles as $handler => $style) {
			$deps = isset( $style['deps'] ) ? $style['deps'] : false;	// dependencis	 
			wp_register_style( $handler, $style['src'], $deps, $style['version'] );
		}
 
		wp_enqueue_style( 'multistep-checkout-style' ); 
		wp_enqueue_script( 'multistep-checkout-scripts' );
		wp_enqueue_script( 'multistep-checkout-billing-populator' );
  
	}
}
