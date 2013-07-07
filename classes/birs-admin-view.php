<?php

class BIRS_Admin_View {

    public $page_hook;

    function __construct() {
        add_action('admin_init', array(&$this, 'admin_init'));
        add_action('init', array(&$this, 'init'));
    }

    function init() {
        
    }

    function admin_init() {
        add_action('admin_enqueue_scripts', array(&$this, 'add_admin_scripts'));
    }
    
    function can_add_admin_scripts($hook) {
        return $this->page_hook == $hook;
    }

    function add_admin_scripts($hook) {
        if ($this->can_add_admin_scripts($hook)) {
            $this->do_add_admin_scripts();
        }
    }
    
    function do_add_admin_scripts() {
        $scripts = $this->get_admin_scripts();
        foreach ($scripts as $script) {
            if(is_array($script)) {
                wp_enqueue_script($script[0]);
                wp_localize_script($script[0], $script[1], $script[2]);
            } else {
                wp_enqueue_script($script);
            }
        }
        $styles = $this->get_admin_styles();
        foreach ($styles as $style) {
            wp_enqueue_style($style);
        }
    }

    function get_admin_scripts() {
        return array();
    }

    function get_admin_styles() {
        return array();
    }

    function get_util() {
        return BIRS_Util::get_instance();
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

    function save_field_float($post_id, $field_name) {
        if (isset($_POST[$field_name])) {
            $value = floatval($_POST[$field_name]);
            update_post_meta($post_id, '_' . $field_name, $value);
        }
    }
}

?>
