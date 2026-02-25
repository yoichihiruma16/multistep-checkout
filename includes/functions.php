<?php 
 
/**
* Get the value of a settings field 
*/
function wpx_get_option( $option, $section, $default = '' ) {
 
    $options = get_option( $section );
 
    if ( isset( $options[$option] ) ) {
      return $options[$option];
    }
 
    return $default;
}
 