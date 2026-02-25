<?php 

namespace MultiStep_Checkout\Admin;

/**
 * The Meta Box Handler Class
 */
class Meta_Box {

    function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_custom_form_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_custom_form_meta_box' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /**
     * Enqueue admin scripts for drag and drop
     */
    public function enqueue_admin_scripts( $hook ) {
        global $post;
        
        if ( ( $hook === 'post.php' || $hook === 'post-new.php' ) && isset( $post->post_type ) && $post->post_type === 'product' ) {
            wp_enqueue_script( 'jquery-ui-sortable' );
        }
    }

    /**
     * Add meta box for custom form selection
     */
    public function add_custom_form_meta_box() {
        add_meta_box(
            'msc_custom_form_meta_box',
            esc_html__( 'Custom Form', 'multistep-checkout' ),
            array( $this, 'render_custom_form_meta_box' ),
            'product',
            'side',
            'default'
        );
    }

    /**
     * Render meta box content
     */
    public function render_custom_form_meta_box( $post ) {
        wp_nonce_field( 'msc_save_custom_form', 'msc_custom_form_nonce' );
        
        $selected_forms = get_post_meta( $post->ID, '_msc_custom_form_ids', true );
        if ( empty( $selected_forms ) ) {
            // Check for old single form format
            $old_form = get_post_meta( $post->ID, '_msc_custom_form_id', true );
            if ( ! empty( $old_form ) ) {
                $selected_forms = array( $old_form );
            } else {
                $selected_forms = array();
            }
        }
        
        // Get Formidable forms
        $forms = $this->get_formidable_forms();
        
        // Separate selected and unselected forms
        $selected_form_objects = array();
        $unselected_form_objects = array();
        
        foreach ( $forms as $form ) {
            if ( in_array( $form->id, $selected_forms ) ) {
                $selected_form_objects[ $form->id ] = $form;
            } else {
                $unselected_form_objects[] = $form;
            }
        }
        
        // Sort selected forms by saved order
        $sorted_selected_forms = array();
        foreach ( $selected_forms as $form_id ) {
            if ( isset( $selected_form_objects[ $form_id ] ) ) {
                $sorted_selected_forms[] = $selected_form_objects[ $form_id ];
            }
        }
        ?>
        <div class="msc-custom-form-field">
            <p style="margin-top: 0;">
                <strong><?php esc_html_e( 'Select Formidable Forms:', 'multistep-checkout' ); ?></strong>
            </p>
            
            <?php if ( ! empty( $forms ) ) : ?>
                <!-- Selected Forms (Sortable) -->
                <div id="msc-selected-forms" style="margin-bottom: 15px;">
                    <p style="margin: 5px 0; font-weight: 600; color: #2271b1;">
                        <?php esc_html_e( 'Selected Forms (Drag to reorder):', 'multistep-checkout' ); ?>
                    </p>
                    <div id="msc-sortable-forms" style="min-height: 40px; border: 2px dashed #2271b1; padding: 5px; background: #f0f6fc; border-radius: 4px;">
                        <?php if ( ! empty( $sorted_selected_forms ) ) : ?>
                            <?php foreach ( $sorted_selected_forms as $form ) : ?>
                                <div class="msc-form-item msc-selected" data-form-id="<?php echo esc_attr( $form->id ); ?>" style="cursor: move; padding: 10px; margin: 5px 0; background: white; border: 1px solid #2271b1; border-radius: 3px; display: flex; align-items: center; justify-content: space-between;">
                                    <div style="display: flex; align-items: center; flex: 1;">
                                        <span class="dashicons dashicons-menu" style="color: #2271b1; margin-right: 10px;"></span>
                                        <input 
                                            type="checkbox" 
                                            name="msc_custom_form_ids[]" 
                                            value="<?php echo esc_attr( $form->id ); ?>" 
                                            checked
                                            style="margin-right: 8px;"
                                        />
                                        <span style="vertical-align: middle; font-weight: 600;"><?php echo esc_html( $form->name ); ?></span>
                                    </div>
                                    <span class="dashicons dashicons-trash msc-remove-form" style="color: #d63638; cursor: pointer;" title="<?php esc_attr_e( 'Remove', 'multistep-checkout' ); ?>"></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p style="text-align: center; color: #666; padding: 10px 0; margin: 0;">
                                <?php esc_html_e( 'No forms selected. Check forms below to add them here.', 'multistep-checkout' ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Available Forms -->
                <div id="msc-available-forms">
                    <p style="margin: 5px 0; font-weight: 600;">
                        <?php esc_html_e( 'Available Forms:', 'multistep-checkout' ); ?>
                    </p>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                        <?php foreach ( $unselected_form_objects as $form ) : ?>
                            <label class="msc-form-item" data-form-id="<?php echo esc_attr( $form->id ); ?>" style="display: block; margin-bottom: 8px; padding: 8px; background: white; border-radius: 3px; cursor: pointer;">
                                <input 
                                    type="checkbox" 
                                    name="msc_custom_form_ids[]" 
                                    value="<?php echo esc_attr( $form->id ); ?>" 
                                    style="margin-right: 8px;"
                                />
                                <span style="vertical-align: middle;"><?php echo esc_html( $form->name ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else : ?>
                <p style="color: #d63638;"><?php esc_html_e( 'No Formidable forms found. Please create a form first.', 'multistep-checkout' ); ?></p>
            <?php endif; ?>
            
            <p class="description" style="margin: 10px 0 0;">
                <?php esc_html_e( 'Check forms to add them to the selected list. Drag selected forms to change their order in checkout.', 'multistep-checkout' ); ?>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Make selected forms sortable
            $('#msc-sortable-forms').sortable({
                placeholder: 'msc-sortable-placeholder',
                handle: '.dashicons-menu',
                axis: 'y',
                opacity: 0.7,
                cursor: 'move'
            });
            
            // Add CSS for placeholder
            $('<style>.msc-sortable-placeholder { background: #e3f2fd; border: 2px dashed #2271b1; height: 50px; margin: 5px 0; border-radius: 3px; }</style>').appendTo('head');
            
            // Handle checkbox changes in available forms
            $('#msc-available-forms').on('change', 'input[type="checkbox"]', function() {
                var $checkbox = $(this);
                var $label = $checkbox.closest('.msc-form-item');
                var formId = $label.data('form-id');
                var formName = $label.find('span').text();
                
                if ($checkbox.is(':checked')) {
                    // Move to selected area
                    var $newItem = $('<div class="msc-form-item msc-selected" data-form-id="' + formId + '" style="cursor: move; padding: 10px; margin: 5px 0; background: white; border: 1px solid #2271b1; border-radius: 3px; display: flex; align-items: center; justify-content: space-between;">' +
                        '<div style="display: flex; align-items: center; flex: 1;">' +
                        '<span class="dashicons dashicons-menu" style="color: #2271b1; margin-right: 10px;"></span>' +
                        '<input type="checkbox" name="msc_custom_form_ids[]" value="' + formId + '" checked style="margin-right: 8px;" />' +
                        '<span style="vertical-align: middle; font-weight: 600;">' + formName + '</span>' +
                        '</div>' +
                        '<span class="dashicons dashicons-trash msc-remove-form" style="color: #d63638; cursor: pointer;" title="Remove"></span>' +
                        '</div>');
                    
                    $('#msc-sortable-forms').append($newItem);
                    $label.remove();
                    
                    // Remove "no forms" message if exists
                    $('#msc-sortable-forms p').remove();
                }
            });
            
            // Handle remove button click
            $('#msc-sortable-forms').on('click', '.msc-remove-form', function() {
                var $item = $(this).closest('.msc-form-item');
                var formId = $item.data('form-id');
                var formName = $item.find('span:not(.dashicons)').text();
                
                // Move back to available area
                var $newLabel = $('<label class="msc-form-item" data-form-id="' + formId + '" style="display: block; margin-bottom: 8px; padding: 8px; background: white; border-radius: 3px; cursor: pointer;">' +
                    '<input type="checkbox" name="msc_custom_form_ids[]" value="' + formId + '" style="margin-right: 8px;" />' +
                    '<span style="vertical-align: middle;">' + formName + '</span>' +
                    '</label>');
                
                $('#msc-available-forms > div').append($newLabel);
                $item.remove();
                
                // Show "no forms" message if all removed
                if ($('#msc-sortable-forms .msc-form-item').length === 0) {
                    $('#msc-sortable-forms').html('<p style="text-align: center; color: #666; padding: 10px 0; margin: 0;">No forms selected. Check forms below to add them here.</p>');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Get all Formidable forms
     */
    private function get_formidable_forms() {
        global $wpdb;
        
        // Check if Formidable Forms is active
        if ( ! class_exists( 'FrmForm' ) ) {
            return array();
        }
        
        // Get forms from Formidable Forms
        $forms = $wpdb->get_results( 
            "SELECT id, name FROM {$wpdb->prefix}frm_forms WHERE status = 'published' ORDER BY name ASC" 
        );
        
        return $forms;
    }

    /**
     * Save meta box data
     */
    public function save_custom_form_meta_box( $post_id ) {
        // Check if nonce is set
        if ( ! isset( $_POST['msc_custom_form_nonce'] ) ) {
            return;
        }

        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['msc_custom_form_nonce'], 'msc_save_custom_form' ) ) {
            return;
        }

        // Check if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check user permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save or delete the meta value
        if ( isset( $_POST['msc_custom_form_ids'] ) && ! empty( $_POST['msc_custom_form_ids'] ) ) {
            $form_ids = array_map( 'sanitize_text_field', $_POST['msc_custom_form_ids'] );
            update_post_meta( $post_id, '_msc_custom_form_ids', $form_ids );
            
            // Keep backward compatibility - also save first form as single value
            if ( ! empty( $form_ids ) ) {
                update_post_meta( $post_id, '_msc_custom_form_id', $form_ids[0] );
            }
        } else {
            delete_post_meta( $post_id, '_msc_custom_form_ids' );
            delete_post_meta( $post_id, '_msc_custom_form_id' );
        }
    }
}
