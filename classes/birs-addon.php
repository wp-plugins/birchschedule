<?php

class BIRS_Addon {

    function __construct() {
        $this->util = BIRS_Util::get_instance();
        //add_action('plugins_loaded', array($this, 'load_i18n'));
    }

    function get_addon_dir_name() {
        return '';
    }

    function load_i18n() {
        load_plugin_textdomain('birchschedule', false, 'birchschedule/addons/' . $this->get_addon_dir_name() . '/languages');
    }
    
    function get_util() {
        return $this->util;
    }

}

?>
