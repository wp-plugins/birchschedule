<?php

final class Birchschedule_Model_Facade_Imp {
    
    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->model;
    }

    static function check_password($email, $password) {
        $user = get_user_by('email', $email);
        if(!$user) {
            return false;
        }
        return wp_check_password($password, $user->user_pass, $user->ID);
    }

    static function get_appointment_meta_keys() {
        $meta_keys = array(
            '_birs_appointment_service', '_birs_appointment_staff',
            '_birs_appointment_location', '_birs_appointment_price',
            '_birs_appointment_timestamp', '_birs_appointment_duration',
            '_birs_appointment_padding_before', '_birs_appointment_padding_after',
            '_birs_appointment_client', '_birs_appointment_payment_status',
            '_birs_appointment_uid'
        );
        return $meta_keys;
    }

    static function get_client_by_email($email, $config) {
        global $birchschedule;
        $criteria = array(
            'post_type' => 'birs_client',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_birs_client_email',
                    'value' => $email
                )
            )
        );
        $clients = $birchschedule->model->query($criteria, $config);
        if (sizeof($clients) > 0) {
            $client = array_shift(array_values($clients));
            return $client;
        }
        return false;
    }

    static function get_client_meta_keys() {
        $client_meta_keys = array(
            '_birs_client_name_first', '_birs_client_name_last',
            '_birs_client_email', '_birs_client_phone',
            '_birs_client_address1', '_birs_client_address2',
            '_birs_client_city', '_birs_client_state',
            '_birs_client_province', '_birs_client_country',
            '_birs_client_zip'
        );
        return $client_meta_keys;   
    }

    static function get_meta_key_label($meta_key) {
        return '';
    }

    static function get_services_by_location($location_id) {
        global $birchschedule;
        birch_assert($birchschedule->model->is_valid_id($location_id)) ;

        $location = array(
            'ID' => $location_id
        );
        $services = $birchschedule->model->query(
            array(
                'post_type' => 'birs_service',
                'order' => 'ASC',
                'orderby' => 'title'
            ),
            array(
                'meta_keys' => array(
                    '_birs_service_assigned_locations'
                ),
                'base_keys' => array(
                    'post_title'
                )
            )
        );
        $assigned_services = array();
        foreach($services as $service) {
            $assigned_locations = $service['_birs_service_assigned_locations'];
            if($assigned_locations) {
                if(isset($assigned_locations[$location_id])) {
                    $assigned_services[$service['ID']] = $service['post_title'];
                }
            }
        }
        return $assigned_services;
    }

    static function get_staff_by_location($location_id) {
        global $birchschedule;

        $staff = $birchschedule->model->query(
                        array(
                            'post_type' => 'birs_staff',
                            'order' => 'ASC',
                            'orderby' => 'title'
                        ),
                        array(
                            'meta_keys' => array(
                                '_birs_staff_schedule'
                            ),
                            'base_keys' => array(
                                'post_title'
                            )
                        )
        );
        $assigned_staff = array();
        foreach ($staff as $the_staff) {
            $staff_schedule = $the_staff['_birs_staff_schedule'];
            if(isset($staff_schedule[$location_id])) {
                $location_schedule = $staff_schedule[$location_id];
            } else {
                $location_schedule = array();
            }
            if (isset($location_schedule['schedules']) && 
                sizeof($location_schedule['schedules']) > 0) {

                $assigned_staff[$the_staff['ID']] = $the_staff['post_title'];
            }
        }
        return $assigned_staff;
    }

    static function get_services_by_staff($staff_id) {

    }

    static function get_staff_by_service($service_id) {

    }

    static function get_locations_map() {
        global $birchschedule;
        $locations = $birchschedule->model->query(
            array(
                'post_type' => 'birs_location',
                'order' => 'ASC',
                'orderby' => 'title'
            ),
            array(
                'base_keys' => array(
                    'post_title'
                ),
                'meta_keys' => array()
            )
        );
        return $locations;
    }

    static function get_locations_services_map() {
        global $birchschedule;

        $map = array();
        $locations = $birchschedule->model->query(
            array(
                'post_type' => 'birs_location'
            ),
            array(
                'base_keys' => array(),
                'meta_keys' => array()
            )
        );
        $services = $birchschedule->model->query(
            array(
                'post_type' => 'birs_service',
                'order' => 'ASC',
                'orderby' => 'title'
            ),
            array(
                'base_keys' => array(
                    'post_title'
                ),
                'meta_keys' => array()
            )
        );
        $services_map = array();
        foreach($services as $service_id => $service) {
            $services_map[$service_id] = $service['post_title'];
        }
        foreach ($locations as $location) {
            $map[$location['ID']] = $services_map;
        }
        return $map;
    }

    static function get_locations_staff_map() {
        global $birchschedule;

        $map = array();
        $locations = $birchschedule->model->query(
            array(
                'post_type' => 'birs_location'
            ),
            array(
                'base_keys' => array(),
                'meta_keys' => array()
            )
        );
        foreach ($locations as $location) {
            $map[$location['ID']] = $birchschedule->model->get_staff_by_location($location['ID']);
        }
        return $map;
    }

    static function get_services_staff_map() {
        global $birchschedule;

        $map = array();
        $services = $birchschedule->model->query(
            array(
                'post_type' => 'birs_service'
            ),
            array(
                'meta_keys' => array(
                    '_birs_assigned_staff'
                ),
                'base_keys' => array()
            )
        );
        foreach ($services as $service) {
            $assigned_staff_ids = $service['_birs_assigned_staff'];
            $staff = $birchschedule->model->query(
                array(
                    'post_type' => 'birs_staff'
                ),
                array(
                    'base_keys' => array(
                        'post_title'
                    ),
                    'meta_keys' => array()
                )
            );
            $assigned_staff = array();
            foreach ($staff as $thestaff) {
                if (array_key_exists($thestaff['ID'], $assigned_staff_ids)) {
                    $assigned_staff[$thestaff['ID']] = $thestaff['post_title'];
                }
                $map[$service['ID']] = $assigned_staff;
            }
        }
        return $map;
    }

    static function get_locations_listing_order() {
        global $birchschedule;

        $locations = $birchschedule->model->query(
            array(
                'post_type' => 'birs_location',
                'order' => 'ASC',
                'orderby' => 'title'
            ),
            array(
                'base_keys' => array(
                    'post_title'
                ),
                'meta_keys' => array()
            )
        );
        $locations_order = array_keys($locations);
        return $locations_order;
    }

    static function get_staff_listing_order() {
        global $birchschedule;

        $staff = $birchschedule->model->query(
            array(
                'post_type' => 'birs_staff',
                'order' => 'ASC',
                'orderby' => 'title'
            ),
            array(
                'base_keys' => array(
                    'post_title'
                ),
                'meta_keys' => array()
            )
        );
        $staff_order = array_keys($staff);
        return $staff_order;
    }

    static function get_services_listing_order() {
        global $birchschedule;

        $services = $birchschedule->model->query(
            array(
                'post_type' => 'birs_service',
                'order' => 'ASC',
                'orderby' => 'title'
            ),
            array(
                'base_keys' => array(
                    'post_title'
                ),
                'meta_keys' => array()
            )
        );
        return array_keys($services);
    }

    static function get_services_prices_map() {
        global $birchschedule;
        $services = $birchschedule->model->query(
            array('post_type' => 'birs_service'),
            array(
                'meta_keys' => array('_birs_service_price', '_birs_service_price_type'),
                'base_keys' => array()
            )
        );
        $price_map = array();
        foreach ($services as $service) {
            $price_map[$service['ID']] = array(
                'price' => $service['_birs_service_price'],
                'price_type' => $service['_birs_service_price_type']
            );
        }
        return $price_map;
    }

    static function get_services_duration_map() {
        global $birchschedule;
        $services = $birchschedule->model->query(
            array('post_type' => 'birs_service'),
            array(
                'meta_keys' => array(
                    '_birs_service_length',
                    '_birs_service_length_type'
                ),
                'base_keys' => array()
            )
        );
        $duration_map = array();
        foreach ($services as $service) {
            $duration_map[$service['ID']] = array(
                'duration' => $birchschedule->model->get_service_length($service['ID'])
            );
        }
        return $duration_map;
    }

    static function query_appointments($start, $end, $location_id, 
        $staff_id, $config) {
        global $birchschedule;

        $meta_query = array(
            array('key' => '_birs_appointment_timestamp',
                'value' => $start,
                'compare' => '>=',
                'type' => 'SIGNED'
            ), array('key' => '_birs_appointment_timestamp',
                'value' => $end,
                'compare' => '<=',
                'type' => 'SIGNED')
        );
        if($location_id != -1) {
            $meta_query[] = array('key' => '_birs_appointment_location',
                'value' => $location_id,
                'type' => 'UNSIGNED');
        }
        if ($staff_id != -1) {
            $meta_query[] = array('key' => '_birs_appointment_staff',
                'value' => $staff_id,
                'type' => 'UNSIGNED');
        }
        $appointments = $birchschedule->model->query(array(
                    'post_type' => 'birs_appointment',
                    'nopaging' => true,
                    'meta_query' => $meta_query
                ), $config);
        $new_appointments = array();
        foreach($appointments as $appointment) {
            $appointment['post_title'] = 
                $birchschedule->model->get_appointment_title($appointment['ID']);
            $new_appointments[$appointment['ID']] = $appointment;
        }
        return $new_appointments;
    }

    static function apply_currency_symbol($val, $curreny_code) {
        global $birchpress;
        
        $currencies = $birchpress->util->get_currencies();
        $currency = $currencies[$curreny_code];
        $symbol = $currency['symbol_right'];
        if ($symbol == '') {
            $symbol = $currency['symbol_left'];
        }
        if ($currency['symbol_right']) {
            $val .= $symbol;
        } else {
            $val = $symbol . $val;
        }

        return $val;
    }

    static function get_currency_code() {
        return 'USD';
    }

    static function get_cut_off_time() {
        return 1;
    }

    static function get_future_time() {
        return 360;
    }

    static function get_time_before_cancel() {
        return 24;
    }

    static function get_time_before_reschedule() {
        return 24;
    }

    static function if_cancel_appointment_outoftime($appointment_id) {
        $time_before_cancel = self::$package->get_time_before_cancel();
        $appointment = self::$package->get($appointment_id, array(
            'base_keys' => array(),
            'meta_keys' => array(
                '_birs_appointment_timestamp'
            )
        ));
        if(!$appointment) {
            return new WP_Error('appointment_nonexist', 'The appointment does not exist.');
        }
        if($appointment['_birs_appointment_timestamp'] - time() > $time_before_cancel * 60 * 60) {
            return true;
        } else {
            return false;
        }
    }

    static function if_reschedule_appointment_outoftime($appointment_id) {
        $time_before_reschedule = self::$package->get_time_before_reschedule();
        $appointment = self::$package->get($appointment_id, array(
            'base_keys' => array(),
            'meta_keys' => array(
                '_birs_appointment_timestamp'
            )
        ));
        if(!$appointment) {
            return new WP_Error('appointment_nonexist', 'The appointment does not exist.');
        }
        if($appointment['_birs_appointment_timestamp'] - time() > $time_before_reschedule * 60 * 60) {
            return true;
        } else {
            return false;
        }
    }

    static function get_staff_daysoff($staff_id) {
        global $birchschedule;

        $staff = $birchschedule->model->get($staff_id, array(
            'base_keys' => array(),
            'meta_keys' => array('_birs_staff_dayoffs')
        ));
        return $staff['_birs_staff_dayoffs'];
    }

    static function get_all_daysoff() {
        global $birchschedule;

        $staff = $birchschedule->model->query(
                    array(
                        'post_type' => 'birs_staff'
                    ),
                    array(
                        'meta_keys' => array(),
                        'base_keys' => array()
                    )
                );
        $dayoffs = array();
        foreach (array_values($staff) as $thestaff) {
            $dayoffs[$thestaff['ID']] = 
                $birchschedule->model->get_staff_daysoff($thestaff['ID']);
        }
        return $dayoffs;
    }

}
Birchschedule_Model_Facade_Imp::init_vars();
