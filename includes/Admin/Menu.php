<?php 

namespace MultiStep_Checkout\Admin;

/**
 * The Menu Handler Class
 */
class Menu { 

    private $settings_api;

    function __construct() {
        $this->settings_api = new Settings_API;
        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
      add_menu_page( 
        esc_html__( 'MultiStep Checkout', 'multistep-checkout' ),
        esc_html__( 'MultiStep Checkout', 'multistep-checkout' ), 
        'manage_options',
        'multistep-checkout',
        [$this, 'plugin_page'],
        'dashicons-cart',
        6
      );  
    }

    function get_settings_sections() {
        $sections = array( 
            array(
                'id'    => 'wpx_styles',
                'title' => esc_html__( 'Button Styles', 'multistep-checkout' ),
                'desc'  => ''
            ) 
        );
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array( 
            'wpx_styles' => array(
                array(
                    'name'    => 'btn_text_color',
                    'label'   => esc_html__( 'Text Color', 'multistep-checkout' ),
                    'desc'    => esc_html__( 'Select color for button text.', 'multistep-checkout' ),
                    'type'    => 'color',
                    'default' => '#333'
                ), 
                array(
                    'name'    => 'btn_bg_color',
                    'label'   => esc_html__( 'Background Color', 'multistep-checkout' ),
                    'desc'    => esc_html__( 'Select background color for button background.', 'multistep-checkout' ),
                    'type'    => 'color',
                    'default' => '#fdd922'
                )
            ) 
        );

        return $settings_fields;
    }

    function plugin_page() { 
        echo '<div class="postbox">';
        echo '<h1 class="wp-heading-inline"> '.esc_html__('MultiStep Checkout','multistep-checkout').' </h1>';
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();
        echo '</div>'; 
    }
 
}