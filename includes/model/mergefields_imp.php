<?php

class Birchschedule_Model_Mergefields_Imp {

    private static $appointment_for_merge_field;

    private function __construct() {

    }

    static function get_full_appointment($id) {
        global $birchschedule, $birchpress;

        $meta_keys = $birchschedule->model->get_appointment_meta_keys();
        $appointment = $birchschedule->model->get($id, array(
            'meta_keys' => $meta_keys,
            'base_keys' => array()
        ));
        $appointment['_birs_appointment_pre_payment_fee'] = 
            $birchschedule->model->get_appointment_pre_payment_fee($id);
        $currency_code = $birchschedule->model->get_currency_code();
        $appointment['_birs_appointment_pre_payment_fee_with_currency'] = 
            $birchschedule->model->
            apply_currency_symbol($appointment['_birs_appointment_pre_payment_fee'], $currency_code);

        $client_meta_keys = $birchschedule->model->get_client_meta_keys();
        $client = $birchschedule->model->get($appointment['_birs_appointment_client'], array(
            'meta_keys' => $client_meta_keys,
            'base_keys' => array('post_title')
        ));
        $staff = $birchschedule->model->get($appointment['_birs_appointment_staff'], array(
            'base_keys' => array(
                'post_title'
            ),
            'meta_keys' => array(
                '_birs_staff_email'
            )
        ));
        $service = $birchschedule->model->get($appointment['_birs_appointment_service'], array(
            'base_keys' => array(
                'post_title'
            ),
            'meta_keys' => array(
            )
        ));
        $location_meta_keys = array(
            '_birs_location_phone', '_birs_location_address1',
            '_birs_location_address2', '_birs_location_city',
            '_birs_location_state', '_birs_location_country',
            '_birs_location_zip'
        );
        $location = $birchschedule->model->get($appointment['_birs_appointment_location'], array(
            'base_keys' => array(
                'post_title'
            ),
            'meta_keys' => $location_meta_keys
        ));
        $appointment = array_merge($client, $service, $staff, $location, $appointment);
        $appointment['site_title'] = get_option('blogname');
        $appointment['admin_email'] = get_option('admin_email');
        return $appointment;
    }

    static function get_merge_fields_map() {
        $merge_fields_map = array(
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
        return $merge_fields_map;
    }

    static function get_merge_field_display_value($field_value) {
        if(is_array($field_value)) {
            $seperator = ', ';
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
    }

    static function apply_merge_field($matches) {
        global $birchschedule;

        $name = $matches[1];
        $appointment = self::$appointment_for_merge_field;
        $map = $birchschedule->model->get_merge_fields_map();
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
            $field_value_display = 
                $birchschedule->model->get_merge_field_display_value($field_value);
            return $field_value_display;
        } else {
            return "";
        }
    }

    static function apply_merge_fields($template, $appointment) {
        self::$appointment_for_merge_field = $appointment;
        $template = preg_replace_callback('/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/',
            array(__CLASS__, 'apply_merge_field'), $template);
        return $template;
    }

}