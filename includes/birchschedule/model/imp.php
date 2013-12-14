<?php

class Birchschedule_Model_Imp {
    
    private function __construct() {

    }

    static function get_appointment_title($appointment_id) {
        global $birchschedule;
        birch_assert($birchschedule->model->is_valid_id($appointment_id));

        $appointment = $birchschedule->model->get($appointment_id, 
            array(
                'base_keys' => array(),
                'meta_keys' => array(
                    '_birs_appointment_client', '_birs_appointment_service'
                )
            )
        );
        $client = $birchschedule->model->get($appointment['_birs_appointment_client'], 
            array(
                'base_keys' => array('post_title'),
                'meta_keys' => array()
            ));
        $service = $birchschedule->model->get($appointment['_birs_appointment_service'],
            array(
                'base_keys' => array('post_title'),
                'meta_keys' => array()
            ));
        return $service['post_title'] . ' - ' . $client['post_title'];
    }

    static function get_appointment_pre_payment_fee($appointment_id) {
        global $birchschedule;

        if(!$birchschedule->model->is_valid_id($appointment_id)) {
            return 0;
        }

        $appointment = $birchschedule->model->get($appointment_id, 
            array(
                'base_keys' => array(),
                'meta_keys' => array(
                    '_birs_appointment_price', '_birs_appointment_service'
                )
            )
        );
        $service = $birchschedule->model->get($appointment['_birs_appointment_service'], 
            array(
                'base_keys' => array(),
                'meta_keys' => array(
                    '_birs_service_pre_payment_fee'
                )
            ));
        $service_pre_payment_fee = $service['_birs_service_pre_payment_fee'];
        if($service_pre_payment_fee) {
            if($service_pre_payment_fee['pre_payment_type'] == 'fixed') {
                return floatval($service_pre_payment_fee['fixed']);
            }
            else if($service_pre_payment_fee['pre_payment_type'] == 'percent') {
                return $service_pre_payment_fee['percent'] * 0.01 *
                    $appointment['_birs_appointment_price'];
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    static function get_service_pre_payment_fee($service_id) {
        global $birchschedule;

        if(!$birchschedule->model->is_valid_id($service_id)) {
            return 0;
        }

        $service = $birchschedule->model->get($service_id, 
            array(
                'base_keys' => array(),
                'meta_keys' => array(
                    '_birs_service_pre_payment_fee',
                    '_birs_service_price'
                )
            ));

        $is_prepayment_enabled = $birchschedule->model->is_prepayment_enabled($service_id);
        if(!$is_prepayment_enabled) {
            return 0;
        }

        $service_pre_payment_fee = $service['_birs_service_pre_payment_fee'];
        if($service_pre_payment_fee) {
            if($service_pre_payment_fee['pre_payment_type'] == 'fixed') {
                return floatval($service_pre_payment_fee['fixed']);
            }
            else if($service_pre_payment_fee['pre_payment_type'] == 'percent') {
                return $service_pre_payment_fee['percent'] * 0.01 *
                    $service['_birs_service_price'];
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    private static function get_service_padding($service_id, $type) {
        global $birchschedule;
        birch_assert($birchschedule->model->is_valid_id($service_id));
        birch_assert($type === 'before' || $type === 'after');

        $service = $birchschedule->model->get($service_id,
            array(
                'meta_keys' => array(
                    '_birs_service_padding', '_birs_service_padding_type'
                ),
                'base_keys' => array()
            ));

        $padding_type = $service['_birs_service_padding_type'];
        if ($padding_type === 'before-and-after' || $padding_type === $type) {
            $padding = $service['_birs_service_padding'];
        } else {
            $padding = 0;
        }
        return $padding;
    }

    static function get_service_padding_before($service_id) {
        return self::get_service_padding($service_id, 'before');
    }

    static function get_service_padding_after($service_id) {
        return self::get_service_padding($service_id, 'after');
    }

    static function get_service_length($service_id) {
        global $birchschedule;
        birch_assert($birchschedule->model->is_valid_id($service_id));

        $service = $birchschedule->model->get($service_id,
            array(
                'meta_keys' => array(
                    '_birs_service_length', '_birs_service_length_type'
                ),
                'base_keys' => array()
            ));
        $length = $service['_birs_service_length'];
        $length_type = $service['_birs_service_length_type'];
        if ($length_type == 'hours') {
            $length = $length * 60;
        }
        return $length;
    }

    static function get_service_length_with_paddings($service_id) {
        return self::get_service_length($service_id) + 
            self::get_service_padding_before($service_id) +
            self::get_service_padding_after($service_id);
    }

    static function get_service_timeslot($service_id) {
        return 15;
    }

    static function get($id, $config) {
        global $birchpress, $birchschedule;

        $model = $birchpress->db->get($id, $config);
        if($model) {
            return $birchschedule->model->post_get($model);
        } else {
            return false;
        }
    }

    static function delete($id) {
        global $birchpress;
        return $birchpress->db->delete($id);
    }

    static function save($model, $config) {
        global $birchschedule, $birchpress;

        $model = $birchschedule->model->pre_save($model, $config);
        return $birchpress->db->save($model, $config);
    }

    static function query($criteria, $config) {
        birch_assert(is_array($config));
        birch_assert(isset($config['base_keys']) && 
            is_array($config['base_keys']));
        birch_assert(isset($config['meta_keys']) && 
            is_array($config['meta_keys']));
        global $birchschedule;

        $criteria = array_merge_recursive(
            array(
                'nopaging' => true,
                'post_status' => 'publish'
            ), 
            $criteria
        );
        $posts = get_posts($criteria);
        $models = array();
        foreach($posts as $post) {
            $model = $birchschedule->model->get($post->ID, $config);
            $models[$post->ID] = $model;
        }
        return $models;
    }

    static function pre_save($model, $config) {
        return $model;
    }

    static function pre_save_birs_client($client, $config) {
        birch_assert(is_array($client) && isset($client['post_type']));
        $name_first = '';
        $name_last = '';
        if(isset($client['_birs_client_name_first'])) {
            $name_first = $client['_birs_client_name_first'];
        }
        if(isset($client['_birs_client_name_last'])) {
            $name_last = $client['_birs_client_name_last'];
        }
        $client['post_title'] = $name_first . ' ' . $name_last;
        return $client;
    }

    static function pre_save_birs_appointment($appointment, $config) {
        birch_assert(is_array($appointment) && isset($appointment['post_type']));
        global $birchschedule;

        if(isset($appointment['_birs_appointment_duration'])) {
            $appointment['_birs_appointment_duration'] = (int) $appointment['_birs_appointment_duration'];
        }
        if(isset($appointment['_birs_appointment_price'])) {
            $appointment['_birs_appointment_price'] = floatval($appointment['_birs_appointment_price']);
        }
        if (!isset($appointment['ID']) ||
            !$birchschedule->model->is_valid_id($appointment['ID'])) {

            $appointment['_birs_appointment_uid'] = uniqid(rand(), true);
        }
        if(!isset($appointment['_birs_appointment_reminded'])) {
            $appointment['_birs_appointment_reminded'] = 0;
        }
        return $appointment;
    }

    static function pre_save_birs_location($location, $config) {
        birch_assert(is_array($location) && isset($location['post_type']));
        return $location;
    }    

    static function pre_save_birs_service($service, $config) {
        if(isset($service['_birs_service_pre_payment_fee'])) {
            $service['_birs_service_pre_payment_fee'] = 
                serialize($service['_birs_service_pre_payment_fee']);
        }
        if(isset($service['_birs_assigned_staff'])) {
            $service['_birs_assigned_staff'] = 
                serialize($service['_birs_assigned_staff']);
        }
        return $service;
    }

    static function pre_save_birs_staff($staff, $config) {
        birch_assert(is_array($staff) && isset($staff['post_type']));

        if(isset($staff['_birs_assigned_services'])) {
            $staff['_birs_assigned_services'] =
                serialize($staff['_birs_assigned_services']);
        }
        
        if(isset($staff['_birs_staff_schedule'])) {
            $staff['_birs_staff_schedule'] =
                serialize($staff['_birs_staff_schedule']);
        }
        return $staff;
    }

    static function pre_save_birs_payment($payment, $config) {
        birch_assert(is_array($payment) && isset($payment['post_type']));

        if(isset($payment['_birs_payment_amount'])) {
            $payment['_birs_payment_amount'] = floatval($payment['_birs_payment_amount']);
        }
        return $payment;
    }

    static function post_get($model) {
        return $model;
    }

    static function post_get_birs_appointment($appointment) {
        birch_assert(is_array($appointment) && isset($appointment['post_type']));
        global $birchpress;

        if(isset($appointment['_birs_appointment_timestamp'])) {
            $timestamp = $appointment['_birs_appointment_timestamp'];
            $appointment['_birs_appointment_datetime'] = 
                $birchpress->util->convert_to_datetime($timestamp);
        }
        return $appointment;
    }

    static function post_get_birs_client($client) {
        birch_assert(is_array($client) && isset($client['post_type']));
        if(isset($client['_birs_client_name_first']) && 
            isset($client['_birs_client_name_last'])) {

            $client['_birs_client_name'] = 
                $client['_birs_client_name_first'] . ' ' . $client['_birs_client_name_last'];
        }
        return $client;
    }

    static function post_get_birs_location($location) {
        birch_assert(is_array($location) && isset($location['post_type']));
        if(isset($location['post_title'])) {
            $location['_birs_location_name'] = $location['post_title'];
        }
        return $location;
    }

    static function post_get_birs_payment($payment) {
        birch_assert(is_array($payment) && isset($payment['post_type']));
        $payment['_birs_payment_amount'] = floatval($payment['_birs_payment_amount']);
        return $payment;
    }

    static function post_get_birs_service($service) {
        birch_assert(is_array($service) && isset($service['post_type']));
        if(isset($service['_birs_service_pre_payment_fee'])) {
            $service['_birs_service_pre_payment_fee'] = 
                unserialize($service['_birs_service_pre_payment_fee']);
            if(!$service['_birs_service_pre_payment_fee']) {
                $service['_birs_service_pre_payment_fee'] = array();
            }
        }
        if(isset($service['_birs_assigned_staff'])) {
            $service['_birs_assigned_staff'] = 
                unserialize($service['_birs_assigned_staff']);
            if(!$service['_birs_assigned_staff']) {
                $service['_birs_assigned_staff'] = array();
            }
        }
        if(isset($service['post_title'])){
            $service['_birs_service_name'] = $service['post_title'];
        }

        return $service;
    }

    static function post_get_birs_staff($staff) {
        birch_assert(is_array($staff) && isset($staff['post_type']));
        if(isset($staff['post_title'])) {
            $staff['_birs_staff_name'] = $staff['post_title'];
        }
        if(isset($staff['_birs_assigned_services'])) {
            $assigned_services = $staff['_birs_assigned_services'];
            $assigned_services = unserialize($assigned_services);
            $assigned_services = $assigned_services ? $assigned_services : array();
            $staff['_birs_assigned_services'] = $assigned_services;
        }
        if(isset($staff['_birs_staff_schedule'])) {
            $schedule = $staff['_birs_staff_schedule'];
            if (!isset($schedule)) {
                $schedule = array();
            } else {
                $schedule = unserialize($schedule);
            }
            $schedule = $schedule ? $schedule : array();
            $staff['_birs_staff_schedule'] = $schedule;
        }
        return $staff;
    }

    static function get_staff_schedule_by_location($staff_id, $location_id) {
        global $birchschedule;
        
        $schedules = array();
        $staff = $birchschedule->model->get($staff_id, array(
                    'base_keys' => array(),
                    'meta_keys' => array('_birs_staff_schedule')
                ));
        $staff_schedule = $staff['_birs_staff_schedule'];
        if(isset($staff_schedule[$location_id])) {
            $location_schedule = $staff_schedule[$location_id];
        } else {
            $location_schedule = array();
        }
        return $location_schedule;
    }

    static function get_default_country() {
        return 'US';        
    }

    static function get_default_state() {
        return false;
    }

    static function update_model_relations($source_id, $source_key, 
            $target_type, $target_key) {

        global $birchschedule;

        $assigned_targets = get_post_meta($source_id, $source_key, true);
        if($assigned_targets) {
            $assigned_targets = unserialize($assigned_targets);
        } else {
            $assigned_targets = array();
        }
        $targets = $birchschedule->model->query(
            array(
                'post_type' => $target_type
            ),
            array(
                'base_keys' => array(),
                'meta_keys' => array()
            )
        );
        foreach ($targets as $target) {
            $assigned_sources = get_post_meta($target['ID'], $target_key, true);
            $assigned_sources = unserialize($assigned_sources);
            if (array_key_exists($target['ID'], $assigned_targets)) {
                $assigned_sources[$source_id] = 'on';
            } else {
                unset($assigned_sources[$source_id]);
            }
            update_post_meta($target['ID'], $target_key, serialize($assigned_sources));
        }
    }

    static function is_prepayment_enabled($service_id) {
        global $birchschedule;

        $service = $birchschedule->model->get($service_id, array(
                'meta_keys' => array(
                    '_birs_service_enable_pre_payment'
                ),
                'base_keys' => array()
            ));
        if(isset($service['_birs_service_enable_pre_payment'])) {
            return $service['_birs_service_enable_pre_payment'];
        } else {
            return false;
        }
    }

    static function cancel_appointment($appointment_id) {
        global $birchschedule;

        $config = array(
            'base_keys' => array('post_status'),
            'meta_keys' => array()
            );
        $appointment = $birchschedule->model->get($appointment_id, $config);
        if($appointment && $appointment['post_type'] === 'birs_appointment') {
            if($appointment['post_status'] === 'cancelled') {
                return new WP_Error('already_cancelled');
            }
            $appointment['post_status'] = 'cancelled';
            $result = $birchschedule->model->save($appointment, $config);
            if(!$result) {
                return new WP_Error('saving_failed');
            }
            return true;
        } else {
            return new WP_Error('not_a_appointment');
        }
    }

}
