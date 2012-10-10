<?php
abstract class BIRS_Admin_View {

    function get_util(){
        return BIRS_Util::getInstance();
    }
    
    function save_field_string($post_id, $field_name) {
        if (isset($_POST[$field_name])) {
            $value = sanitize_text_field($_POST[$field_name]);
            update_post_meta($post_id, '_' . $field_name, $value);
        }
    }

    function save_field_array($post_id, $field_name) {
        if (isset($_POST[$field_name])) {
            $value = serialize($_POST[$field_name]);
        } else {
            $value = serialize(array());
        }
        update_post_meta($post_id, '_' . $field_name, $value);
    }

    function save_field_int($post_id, $field_name) {
        if (isset($_POST[$field_name])) {
            $value = intval($_POST[$field_name]);
            update_post_meta($post_id, '_' . $field_name, $value);
        }
    }
}
?>
