<?php

class BIRS_Addon {

    function __construct() {
        $this->util = BIRS_Util::get_instance();
    }

    function get_addon_dir_name() {
        return '';
    }

    function get_util() {
        return $this->util;
    }

}

?>
