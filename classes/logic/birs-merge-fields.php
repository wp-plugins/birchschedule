<?php

class BIRS_Merge_Fields {
    var $appointment_for_merge_field;
    private static $instance;

    static function get_instance() {
        if(!self::$instance) {
            self::$instance = new BIRS_Merge_Fields();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->util = BIRS_Util::get_instance();
        add_filter('birchschedule_get_appointment_merge_fields_values',
            array($this, 'get_appointment_merge_fields_values'), 20, 2);
        add_filter('birchschedule_replace_merge_fields', 
            array($this, 'replace_merge_fields'), 10, 2);
    }

    function get_merge_field_map() {
        $merge_field_map = array(
            'phone' => '_birs_location_phone',
            'address1' => '_birs_location_address1',
            'address2' => '_birs_location_address2',
            'city' => '_birs_location_city',
            'state' => '_birs_location_state',
            'province' => '_birs_location_state',
            'country' => '_birs_location_country',
            'zip_code' => '_birs_location_zip',
            'datetime' => '_birs_appointment_datetime',
            'client_first_name' => '_birs_client_name_first',
            'client_last_name' => '_birs_client_name_last',
            'deposit' => '_birs_appointment_pre_payment_fee_with_currency',
            'admin_email' => 'admin_email',
            'site_title' => 'site_title'
        ); 
        return $merge_field_map;
    }
    
    function convert_to_datetime($timestamp) {
        return $this->util->convert_to_datetime($timestamp);
    }
    
    function get_appointment_merge_fields_values($appointment, $post_id) {
        return $this->get_appointment($post_id);
    }
    
    function get_appointment($post_id) {
        $meta_keys = array(
            '_birs_appointment_location', '_birs_appointment_service',
            '_birs_appointment_staff', '_birs_appointment_timestamp',
            '_birs_appointment_client', '_birs_appointment_duration',
            '_birs_appointment_price', '_birs_appointment_notes',
            '_birs_appointment_uid'
        );
        $meta_keys = apply_filters('birchschedule_appointment_meta_keys', $meta_keys);
        $appointment = new BIRS_Appointment($post_id, array(
            'meta_keys' => $meta_keys
        ));
        $appointment->load();
        $timestamp = $appointment->_birs_appointment_timestamp;
        $appointment['_birs_appointment_datetime'] = $this->convert_to_datetime($timestamp);
        $appointment['_birs_appointment_pre_payment_fee'] = 
            $appointment->get_appointment_pre_payment_fee();
        $appointment['_birs_appointment_pre_payment_fee_with_currency'] = 
            apply_filters('birchschedule_price', 
                $appointment['_birs_appointment_pre_payment_fee']);
        $client_id = $appointment->_birs_appointment_client;
        $client = $this->get_client($client_id);
        $staff_id = $appointment->_birs_appointment_staff;
        $staff = $this->get_staff($staff_id);
        $service_id = $appointment->_birs_appointment_service;
        $service = $this->get_service($service_id);
        $location_id = $appointment->_birs_appointment_location;
        $location = $this->get_location($location_id);
        $appointment = array_merge($client->get_data(), $service->get_data(),
            $staff->get_data(), $location->get_data(), $appointment->get_data());
        $appointment['site_title'] = get_option('blogname');
        $appointment['admin_email'] = get_option('admin_email');
        return $appointment;
    }
    
    function get_staff($staff_id) {
        $staff = new BIRS_Staff($staff_id, array(
            'base_keys' => array(
                'post_title'
            ),
            'meta_keys' => array(
                '_birs_staff_email'
            )
        ));
        $staff->load();
        $staff['_birs_staff_name'] = $staff->post_title;
        return $staff;
    }
    
    function get_service($service_id) {
        $service = new BIRS_Service($service_id, array(
            'base_keys' => array(
                'post_title'
            ),
            'meta_keys' => array(
            )
        ));
        $service->load();
        $service['_birs_service_name'] = $service->post_title;
        return  $service;
    }
    
    function get_location($location_id) {
        $location = new BIRS_Location($location_id, array(
            'base_keys' => array(
                'post_title'
            ),
            'meta_keys' => array(
                '_birs_location_phone', '_birs_location_address1',
                '_birs_location_address2', '_birs_location_city',
                '_birs_location_state', '_birs_location_country',
                '_birs_location_zip'
            )
        ));
        $location->load();
        $location['_birs_location_name'] = $location->post_title;
        return $location;
    }
    
    function get_client($client_id) {
        $meta_keys = array(
            '_birs_client_name_first', '_birs_client_name_last',
            '_birs_client_email', '_birs_client_phone',
            '_birs_client_address1', '_birs_client_address2',
            '_birs_client_city', '_birs_client_state',
            '_birs_client_province', '_birs_client_country',
            '_birs_client_zip'
        );
        $meta_keys = apply_filters('birchschedule_client_meta_keys', $meta_keys);
        $client = new BIRS_Client($client_id, array(
            'base_keys' => array(
                'post_title'
            ),
            'meta_keys' => $meta_keys
        ));
        $client->load();
        $client['_birs_client_name'] = $client['_birs_client_name_first'] . ' ' . $client['_birs_client_name_last'];
        return $client;
    }

    function get_merge_field_value($matches) {
        $name = $matches[1];
        $appointment = $this->appointment_for_merge_field;
        $map = $this-> get_merge_field_map();
        if(isset($appointment['_birs_client_country']) && 
            $appointment['_birs_client_country'] !== 'US') {
            $map['client_state'] = '_birs_client_province';
        }
        if(isset($map[$name])) {
            $name = $map[$name];
        } else {
            $name = "_birs_" . $name;
        }
        
        if(isset($appointment[$name])) {
            $field_value = $appointment[$name];
            $field_value_display = '';
            if(is_array($field_value)) {
                $seperator = apply_filters('birchschedule_merge_fields_array_seperator', ', ');
                foreach($field_value as $each_value) {
                    $field_value_display .= $each_value . $seperator;
                }
                if($field_value) {
                    $end_pos = -strlen($seperator);
                    $field_value_display = substr($field_value_display, 0, $end_pos);
                }
            } else {
                $field_value_display = $field_value;
            }
            return $field_value_display;
        } else {
            return "";
        }
    }
    
    function replace_merge_fields($template, $appointment) {
        if(is_int($appointment)) {
            $appointment = $this->get_appointment($appointment);
        }
        $this->appointment_for_merge_field = $appointment;
        $template = preg_replace_callback('/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/',
            array($this, 'get_merge_field_value'), $template);
        return $template;
    }
    
    function get_util() {
        return $this->util;
    }

}

?>
