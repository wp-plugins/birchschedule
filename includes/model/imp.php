<?php

final class Birchschedule_Model_Imp {

    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->model;
    }

    static function init() {
        add_filter('birchbase_db_get_essential_post_columns', 
            array(self::$package, 'add_more_essential_columns'), 20, 2);
    }

    static function is_valid_id( $id ) {
        global $birchpress;

        return $birchpress->db->is_valid_id( $id );
    }

    static function add_more_essential_columns($columns, $post_type) {
        if($post_type == 'birs_staff') {
            $columns[] = 'post_title';
            $columns[] = 'post_content';
        }
        if($post_type == 'birs_service') {
            $columns[] = 'post_title';
            $columns[] = 'post_content';
        }
        return $columns;
    }

    static function get_service_pre_payment_fee( $service_id ) {
        global $birchschedule;

        if ( !$birchschedule->model->is_valid_id( $service_id ) ) {
            return 0;
        }

        $service = $birchschedule->model->get( $service_id,
            array(
                'base_keys' => array(),
                'meta_keys' => array(
                    '_birs_service_pre_payment_fee',
                    '_birs_service_price'
                )
            ) );

        $is_prepayment_enabled = $birchschedule->model->is_prepayment_enabled( $service_id );
        if ( !$is_prepayment_enabled ) {
            return 0;
        }

        $service_pre_payment_fee = $service['_birs_service_pre_payment_fee'];
        if ( $service_pre_payment_fee ) {
            if ( $service_pre_payment_fee['pre_payment_type'] == 'fixed' ) {
                return floatval( $service_pre_payment_fee['fixed'] );
            }
            else if ( $service_pre_payment_fee['pre_payment_type'] == 'percent' ) {
                    return $service_pre_payment_fee['percent'] * 0.01 *
                        $service['_birs_service_price'];
                } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    private static function get_service_padding( $service_id, $type ) {
        global $birchschedule;
        birch_assert( $birchschedule->model->is_valid_id( $service_id ) );
        birch_assert( $type === 'before' || $type === 'after' );

        $service = $birchschedule->model->get( $service_id,
            array(
                'meta_keys' => array(
                    '_birs_service_padding', '_birs_service_padding_type'
                ),
                'base_keys' => array()
            ) );

        $padding_type = $service['_birs_service_padding_type'];
        if ( $padding_type === 'before-and-after' || $padding_type === $type ) {
            $padding = $service['_birs_service_padding'];
        } else {
            $padding = 0;
        }
        return $padding;
    }

    static function get_service_padding_before( $service_id ) {
        return self::get_service_padding( $service_id, 'before' );
    }

    static function get_service_padding_after( $service_id ) {
        return self::get_service_padding( $service_id, 'after' );
    }

    static function get_service_length( $service_id ) {
        global $birchschedule;
        birch_assert( $birchschedule->model->is_valid_id( $service_id ) );

        $service = $birchschedule->model->get( $service_id,
            array(
                'meta_keys' => array(
                    '_birs_service_length', '_birs_service_length_type'
                ),
                'base_keys' => array()
            ) );
        $length = $service['_birs_service_length'];
        $length_type = $service['_birs_service_length_type'];
        if ( $length_type == 'hours' ) {
            $length = $length * 60;
        }
        return $length;
    }

    static function get_service_length_with_paddings( $service_id ) {
        return self::get_service_length( $service_id ) +
            self::get_service_padding_before( $service_id ) +
            self::get_service_padding_after( $service_id );
    }

    static function get_service_price( $service_id ) {
        global $birchschedule;

        $service = $birchschedule->model->get( $service_id,
            array(
                'base_keys' => array(),
                'meta_keys' => array(
                    '_birs_service_price'
                )
            ) );
        if ( isset( $service['_birs_service_price'] ) ) {
            return floatval( $service['_birs_service_price'] );
        } else {
            return 0;
        }
    }

    static function get_service_timeslot( $service_id ) {
        return 15;
    }

    static function get_service_capacity( $service_id ) {
        return 1;
    }

    static function get( $post, $config = false ) {
        global $birchpress, $birchschedule;

        $model = $birchpress->db->get( $post, $config );
        if ( $model ) {
            return $birchschedule->model->post_get( $model );
        } else {
            return false;
        }
    }

    static function delete( $id ) {
        global $birchpress;
        return $birchpress->db->delete( $id );
    }

    static function save( $model, $config = false ) {
        global $birchschedule, $birchpress;

        $model = $birchschedule->model->pre_save( $model, $config );
        return $birchpress->db->save( $model, $config );
    }

    static function query( $criteria, $config = false ) {
        global $birchschedule, $birchpress;

        if ( !is_array( $config ) ) {
            $config = array();
        }
        $config['fn_get'] = array( self::$package, 'get' );
        $models = $birchpress->db->query( $criteria, $config );
        return $models;
    }

    static function pre_save( $model, $config ) {
        return $model;
    }

    static function pre_save_birs_client( $client, $config ) {
        birch_assert( is_array( $client ) && isset( $client['post_type'] ) );
        $name_first = '';
        $name_last = '';
        if ( isset( $client['_birs_client_name_first'] ) ) {
            $name_first = $client['_birs_client_name_first'];
        }
        if ( isset( $client['_birs_client_name_last'] ) ) {
            $name_last = $client['_birs_client_name_last'];
        }
        $client['post_title'] = $name_first . ' ' . $name_last;
        return $client;
    }

    static function pre_save_birs_appointment( $appointment, $config ) {
        birch_assert( is_array( $appointment ) && isset( $appointment['post_type'] ) );
        global $birchschedule;

        if ( isset( $appointment['_birs_appointment_duration'] ) ) {
            $appointment['_birs_appointment_duration'] = (int) $appointment['_birs_appointment_duration'];
        }
        return $appointment;
    }

    static function pre_save_birs_location( $location, $config ) {
        birch_assert( is_array( $location ) && isset( $location['post_type'] ) );
        return $location;
    }

    static function pre_save_birs_service( $service, $config ) {
        if ( isset( $service['_birs_service_pre_payment_fee'] ) ) {
            $service['_birs_service_pre_payment_fee'] =
                serialize( $service['_birs_service_pre_payment_fee'] );
        }
        if ( isset( $service['_birs_assigned_staff'] ) ) {
            $service['_birs_assigned_staff'] =
                serialize( $service['_birs_assigned_staff'] );
        }
        return $service;
    }

    static function pre_save_birs_staff( $staff, $config ) {
        birch_assert( is_array( $staff ) && isset( $staff['post_type'] ) );

        if ( isset( $staff['_birs_assigned_services'] ) ) {
            $staff['_birs_assigned_services'] =
                serialize( $staff['_birs_assigned_services'] );
        }

        if ( isset( $staff['_birs_staff_schedule'] ) ) {
            $staff['_birs_staff_schedule'] =
                serialize( $staff['_birs_staff_schedule'] );
        }
        return $staff;
    }

    static function pre_save_birs_payment( $payment, $config ) {
        birch_assert( is_array( $payment ) && isset( $payment['post_type'] ) );

        if ( isset( $payment['_birs_payment_amount'] ) ) {
            $payment['_birs_payment_amount'] = floatval( $payment['_birs_payment_amount'] );
        }
        return $payment;
    }

    static function post_get( $model ) {
        return $model;
    }

    static function post_get_birs_appointment( $appointment ) {
        birch_assert( is_array( $appointment ) && isset( $appointment['post_type'] ) );
        global $birchpress;

        if ( isset( $appointment['_birs_appointment_timestamp'] ) ) {
            $timestamp = $appointment['_birs_appointment_timestamp'];
            $appointment['_birs_appointment_datetime'] =
                $birchpress->util->convert_to_datetime( $timestamp );
        }
        if( !isset($appointment['appointment1on1s'])) {
            $appointment['appointment1on1s'] = array();
        }
        return $appointment;
    }

    static function post_get_birs_client( $client ) {
        birch_assert( is_array( $client ) && isset( $client['post_type'] ) );
        if ( isset( $client['_birs_client_name_first'] ) &&
            isset( $client['_birs_client_name_last'] ) ) {

            $client['_birs_client_name'] =
                $client['_birs_client_name_first'] . ' ' . $client['_birs_client_name_last'];
        }
        return $client;
    }

    static function post_get_birs_location( $location ) {
        birch_assert( is_array( $location ) && isset( $location['post_type'] ) );
        if ( isset( $location['post_title'] ) ) {
            $location['_birs_location_name'] = $location['post_title'];
        }
        return $location;
    }

    static function post_get_birs_payment( $payment ) {
        birch_assert( is_array( $payment ) && isset( $payment['post_type'] ) );
        $payment['_birs_payment_amount'] = floatval( $payment['_birs_payment_amount'] );
        return $payment;
    }

    static function post_get_birs_service( $service ) {
        birch_assert( is_array( $service ) && isset( $service['post_type'] ) );
        if ( isset( $service['_birs_service_pre_payment_fee'] ) ) {
            $service['_birs_service_pre_payment_fee'] =
                unserialize( $service['_birs_service_pre_payment_fee'] );
            if ( !$service['_birs_service_pre_payment_fee'] ) {
                $service['_birs_service_pre_payment_fee'] = array();
            }
        }
        if ( isset( $service['_birs_assigned_staff'] ) ) {
            $service['_birs_assigned_staff'] =
                unserialize( $service['_birs_assigned_staff'] );
            if ( !$service['_birs_assigned_staff'] ) {
                $service['_birs_assigned_staff'] = array();
            }
        }
        if ( isset( $service['post_title'] ) ) {
            $service['_birs_service_name'] = $service['post_title'];
        }

        if ( isset( $service['post_content'] ) ) {
            $service['_birs_service_description'] = $service['post_content'];
        }

        return $service;
    }

    static function post_get_birs_staff( $staff ) {
        birch_assert( is_array( $staff ) && isset( $staff['post_type'] ) );
        if ( isset( $staff['post_title'] ) ) {
            $staff['_birs_staff_name'] = $staff['post_title'];
        }
        if ( isset( $staff['_birs_assigned_services'] ) ) {
            $assigned_services = $staff['_birs_assigned_services'];
            $assigned_services = unserialize( $assigned_services );
            $assigned_services = $assigned_services ? $assigned_services : array();
            $staff['_birs_assigned_services'] = $assigned_services;
        }
        if ( isset( $staff['_birs_staff_schedule'] ) ) {
            $schedule = $staff['_birs_staff_schedule'];
            if ( !isset( $schedule ) ) {
                $schedule = array();
            } else {
                $schedule = unserialize( $schedule );
            }
            $schedule = $schedule ? $schedule : array();
            $staff['_birs_staff_schedule'] = $schedule;
        }
        if ( isset( $staff['post_content'] ) ) {
            $staff['_birs_staff_description'] = $staff['post_content'];
        }
        return $staff;
    }

    static function get_staff_schedule_by_location( $staff_id, $location_id ) {
        global $birchschedule;

        $schedules = array();
        $staff = $birchschedule->model->get( $staff_id, array(
                'base_keys' => array(),
                'meta_keys' => array( '_birs_staff_schedule' )
            ) );
        $staff_schedule = $staff['_birs_staff_schedule'];
        if ( isset( $staff_schedule[$location_id] ) ) {
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

    static function update_model_relations( $source_id, $source_key,
        $target_type, $target_key ) {

        global $birchschedule;

        $assigned_targets = get_post_meta( $source_id, $source_key, true );
        if ( $assigned_targets ) {
            $assigned_targets = unserialize( $assigned_targets );
        }
        if ( !$assigned_targets ) {
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
        foreach ( $targets as $target ) {
            $assigned_sources = get_post_meta( $target['ID'], $target_key, true );
            $assigned_sources = unserialize( $assigned_sources );
            if ( array_key_exists( $target['ID'], $assigned_targets ) ) {
                $assigned_sources[$source_id] = 'on';
            } else {
                unset( $assigned_sources[$source_id] );
            }
            update_post_meta( $target['ID'], $target_key, serialize( $assigned_sources ) );
        }
    }

    static function is_prepayment_enabled( $service_id ) {
        global $birchschedule;

        $service = $birchschedule->model->get( $service_id, array(
                'meta_keys' => array(
                    '_birs_service_enable_pre_payment'
                ),
                'base_keys' => array()
            ) );
        if ( isset( $service['_birs_service_enable_pre_payment'] ) ) {
            return $service['_birs_service_enable_pre_payment'];
        } else {
            return false;
        }
    }

    static function check_password( $email, $password ) {
        $user = get_user_by( 'email', $email );
        if ( !$user ) {
            return false;
        }
        return wp_check_password( $password, $user->user_pass, $user->ID );
    }

    static function get_appointment_fields() {
        $meta_keys = array(
            '_birs_appointment_service', '_birs_appointment_staff',
            '_birs_appointment_location', '_birs_appointment_timestamp',
            '_birs_appointment_uid', '_birs_appointment_duration',
            '_birs_appointment_padding_before', '_birs_appointment_padding_after',
            '_birs_appointment_capacity'
        );
        return $meta_keys;
    }

    static function get_appointment1on1_fields() {
        return
        array(
            '_birs_appointment_id',
            '_birs_client_id',
            '_birs_appointment1on1_payment_status',
            '_birs_appointment1on1_reminded',
            '_birs_appointment1on1_price',
            '_birs_appointment1on1_uid'
        );
    }

    static function get_appointment1on1_custom_fields() {
        return array( '_birs_appointment_notes' );
    }

    static function get_client_by_email( $email, $config ) {
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
        $clients = $birchschedule->model->query( $criteria, $config );
        if ( sizeof( $clients ) > 0 ) {
            $clients_values = array_values( $clients );
            $client = array_shift( $clients_values );
            return $client;
        }
        return false;
    }

    static function get_client_fields() {
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

    static function get_payment_fields() {
        return array(
            '_birs_payment_appointment', '_birs_payment_client',
            '_birs_payment_amount', '_birs_payment_type',
            '_birs_payment_trid', '_birs_payment_notes',
            '_birs_payment_timestamp', '_birs_payment_currency'
        );
    }

    static function get_meta_key_label( $meta_key ) {
        return '';
    }

    static function get_services_by_location( $location_id ) {
        global $birchschedule;
        birch_assert( $birchschedule->model->is_valid_id( $location_id ) ) ;

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
        foreach ( $services as $service ) {
            $assigned_locations = $service['_birs_service_assigned_locations'];
            if ( $assigned_locations ) {
                if ( isset( $assigned_locations[$location_id] ) ) {
                    $assigned_services[$service['ID']] = $service['post_title'];
                }
            }
        }
        return $assigned_services;
    }

    static function get_staff_by_location( $location_id ) {
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
        foreach ( $staff as $the_staff ) {
            $staff_schedule = $the_staff['_birs_staff_schedule'];
            if ( isset( $staff_schedule[$location_id] ) ) {
                $location_schedule = $staff_schedule[$location_id];
            } else {
                $location_schedule = array();
            }
            if ( isset( $location_schedule['schedules'] ) &&
                sizeof( $location_schedule['schedules'] ) > 0 ) {

                $assigned_staff[$the_staff['ID']] = $the_staff['post_title'];
            }
        }
        return $assigned_staff;
    }

    static function get_services_by_staff( $staff_id ) {
        $assigned_services = get_post_meta( $staff_id, '_birs_assigned_services', true );
        $assigned_services = unserialize( $assigned_services );
        if ( $assigned_services === false ) {
            $assigned_services = array();
        }
        return $assigned_services;
    }

    static function get_staff_by_service( $service_id ) {
        $assigned_staff = get_post_meta( $service_id, '_birs_assigned_staff', true );
        $assigned_staff = unserialize( $assigned_staff );
        if ( $assigned_staff === false ) {
            $assigned_staff = array();
        }
        return $assigned_staff;
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

    static function get_services_map() {
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
        return $services;
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
        foreach ( $services as $service_id => $service ) {
            $services_map[$service_id] = $service['post_title'];
        }
        foreach ( $locations as $location ) {
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
        foreach ( $locations as $location ) {
            $map[$location['ID']] = $birchschedule->model->get_staff_by_location( $location['ID'] );
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
        foreach ( $services as $service ) {
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
            foreach ( $staff as $thestaff ) {
                if ( array_key_exists( $thestaff['ID'], $assigned_staff_ids ) ) {
                    $assigned_staff[$thestaff['ID']] = $thestaff['post_title'];
                }
                $map[$service['ID']] = $assigned_staff;
            }
        }
        return $map;
    }

    static function get_services_locations_map() {
        global $birchschedule;

        $map = array();
        $services = $birchschedule->model->query(
            array(
                'post_type' => 'birs_service'
            ),
            array(
                'base_keys' => array(),
                'meta_keys' => array()
            )
        );
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
        $locations_map = array();
        foreach ( $locations as $location_id => $location ) {
            $locations_map[$location_id] = $location['post_title'];
        }
        foreach ( $services as $service ) {
            $map[$service['ID']] = $locations_map;
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
        $locations_order = array_keys( $locations );
        return $locations_order;
    }

    static function get_staff_listing_order_type() {
        return 'by_title';
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
        $staff_order = array_keys( $staff );
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
        return array_keys( $services );
    }

    static function get_services_prices_map() {
        global $birchschedule;
        $services = $birchschedule->model->query(
            array( 'post_type' => 'birs_service' ),
            array(
                'meta_keys' => array( '_birs_service_price', '_birs_service_price_type' ),
                'base_keys' => array()
            )
        );
        $price_map = array();
        foreach ( $services as $service ) {
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
            array( 'post_type' => 'birs_service' ),
            array(
                'meta_keys' => array(
                    '_birs_service_length',
                    '_birs_service_length_type'
                ),
                'base_keys' => array()
            )
        );
        $duration_map = array();
        foreach ( $services as $service ) {
            $duration_map[$service['ID']] = array(
                'duration' => $birchschedule->model->get_service_length( $service['ID'] )
            );
        }
        return $duration_map;
    }

    static function apply_currency_symbol( $val, $curreny_code ) {
        global $birchpress;

        $currencies = $birchpress->util->get_currencies();
        $currency = $currencies[$curreny_code];
        $symbol = $currency['symbol_right'];
        if ( $symbol == '' ) {
            $symbol = $currency['symbol_left'];
        }
        if ( $currency['symbol_right'] ) {
            $val .= $symbol;
        } else {
            $val = $symbol . $val;
        }

        return $val;
    }

    static function get_currency_code() {
        return 'USD';
    }

    static function get_cut_off_time( $staff_id = -1, $location_id = -1, $service_id = -1 ) {
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

    static function get_staff_daysoff( $staff_id ) {
        global $birchschedule;

        $staff = $birchschedule->model->get( $staff_id, array(
                'base_keys' => array(),
                'meta_keys' => array( '_birs_staff_dayoffs' )
            ) );
        $daysoff = json_encode( array() );
        if ( $staff['_birs_staff_dayoffs'] ) {
            $daysoff = $staff['_birs_staff_dayoffs'];
        }
        return $daysoff;
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
        foreach ( array_values( $staff ) as $thestaff ) {
            $dayoffs[$thestaff['ID']] =
                $birchschedule->model->get_staff_daysoff( $thestaff['ID'] );
        }
        return $dayoffs;
    }

    static function get_user_by_staff( $staff_id ) {
        global $birchschedule;

        $staff = $birchschedule->model->get( $staff_id,
            array(
                'base_keys' => array(),
                'meta_keys' => array( '_birs_staff_email' )
            )
        );
        if ( $staff ) {
            $user = WP_User::get_data_by( 'email', $staff['_birs_staff_email'] );
            return $user;
        } else {
            return false;
        }
    }

    static function get_staff_by_user( $user, $config = array() ) {
        $email = $user->user_email;
        $staff = self::$birchschedule->model->query(
            array(
                'post_type' => 'birs_staff',
                'meta_query' => array(
                    array(
                        'key' => '_birs_staff_email',
                        'value' => $email
                    )
                )
            ),
            $config
        );
        if ( $staff ) {
            return array_values( $staff );
        } else {
            return false;
        }
    }

    static function merge_data( $model, $config, $data ) {
        birch_assert( is_array( $config ) && isset( $config['base_keys'] ) &&
            isset( $config['meta_keys'] ) );
        foreach ( $config['base_keys'] as $key ) {
            if ( isset( $data[$key] ) ) {
                $model[$key] = $data[$key];
            } else {
                $model[$key] = null;
            }
        }
        foreach ( $config['meta_keys'] as $key ) {
            $req_key = substr( $key, 1 );
            if ( isset( $data[$key] ) ) {
                $model[$key] = $data[$key];
            } else if ( isset( $data[$req_key] ) ) {
                    $model[$key] = $data[$req_key];
                } else {
                $model[$key] = null;
            }
        }
        return $model;
    }

}

Birchschedule_Model_Imp::init_vars();
