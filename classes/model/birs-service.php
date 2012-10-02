<?php

class BIRS_Service extends BIRS_Model {
    
    public function __construct($id, $options) {
        parent::__construct($id, $options);
        $this['post_type'] = 'birs_service';
    }

    public function get_service_length() {
        $length = $this['_birs_service_length'];
        $length_type = $this['_birs_service_length_type'];
        if ($length_type == 'hours') {
            $length = $length * 60;
        }
        $padding = $this['_birs_service_padding'];
        $padding_type = $this['_birs_service_padding_type'];
        if ($padding_type == 'before-and-after') {
            $padding *= 2;
        }
        return $length + $padding;
    }

    public function get_assigned_staff_ids() {
        $assigned_staff = $this['_birs_assigned_staff'];
        $assigned_staff = unserialize($assigned_staff);
        $assigned_staff = $assigned_staff ? $assigned_staff : array();
        return $assigned_staff;
    }

}

?>
