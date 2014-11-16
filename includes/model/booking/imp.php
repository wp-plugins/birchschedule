<?php

final class Birchschedule_Model_Booking_Imp {

    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->model->booking;
    }

    static function init() {
        add_action( 'birchschedule_model_schedule_get_staff_avaliable_time_after',
            array( self::$package, 'record_fully_booked_day' ), 20, 5 );

        add_action( 'admin_post_birchschedule_model_booking_check_if_fully_booked',
            array( self::$package, 'admin_post_check_if_fully_booked' ), 20 );

        add_action( 'admin_post_nopriv_birchschedule_model_booking_check_if_fully_booked',
            array( self::$package, 'admin_post_check_if_fully_booked' ), 20 );

        add_action( 'admin_post_birchschedule_model_booking_recheck_fully_booked_days',
            array( self::$package, 'admin_post_recheck_fully_booked_days' ), 20 );

        add_action( 'admin_post_nopriv_birchschedule_model_booking_recheck_fully_booked_days',
            array( self::$package, 'admin_post_recheck_fully_booked_days' ), 20 );

        add_action( 'birchschedule_model_booking_do_change_appointment1on1_status_after',
            array( self::$package, 'asyn_check_if_fully_booked' ), 20, 1 );

        add_action( 'birchschedule_model_booking_do_reschedule_appointment1on1_after',
            array( self::$package, 'asyn_check_if_fully_booked' ), 20, 1 );

        add_action( 'birchschedule_model_booking_cancel_appointment1on1_after',
            array( self::$package, 'asyn_check_if_fully_booked' ), 20, 1 );
    }

    static function get_appointment_title( $appointment ) {
        global $birchschedule;

        $service = $birchschedule->model->get( $appointment['_birs_appointment_service'],
            array(
                'base_keys' => array( 'post_title' ),
                'meta_keys' => array()
            ) );
        $appointment1on1s = $appointment['appointment1on1s'];
        if ( sizeof( $appointment1on1s ) > 1 ) {
            $title = $service['post_title'] . ' - ' . sprintf( __( '%s Clients', 'birchschedule' ),
                sizeof( $appointment1on1s ) );
        }
        else if ( sizeof( $appointment1on1s ) == 1 ) {
                $appointment1on1s = array_values( $appointment1on1s );
                $appointment1on1 = $appointment1on1s[0];
                $title = $service['post_title'] . ' - ' . $appointment1on1['_birs_client_name'];
            }
        else {
            $title = $service['post_title'];
        }
        return $title;

    }

    static function get_appointment1on1s_by_appointment( $appointment_id, $config = array() ) {
        $config = array_merge(
            array(
                'status' => 'any'
            ),
            $config
        );
        $appointments = self::$package->query_appointments(
            array(
                'status' => $config['status'],
                'appointment_id' => $appointment_id
            ),
            $config
        );
        if ( $appointments ) {
            $appointment1on1s = $appointments[$appointment_id]['appointment1on1s'];
        } else {
            $appointment1on1s = array();
        }
        return $appointment1on1s;
    }

    static function get_appointment1on1( $appointment_id, $client_id, $config = array() ) {
        global $birchschedule;

        if ( !$config ) {
            $fields = $birchschedule->model->get_appointment1on1_custom_fields();
            $fields = array_merge( $fields, array(
                    '_birs_client_id', '_birs_appointment_id', 'post_status'
                ) );
            $config = array(
                'appointment1on1_keys' => $fields
            );
        }

        $config = array_merge(
            array(
                'status' => 'any'
            ),
            $config
        );
        $appointments = self::$package->query_appointments(
            array(
                'client_id' => $client_id,
                'appointment_id' => $appointment_id,
                'post_status' => $config['status']
            ),
            $config
        );
        if ( $appointments ) {
            $appointment1on1s = $appointments[$appointment_id]['appointment1on1s'];
            $appointment1on1s = array_values( $appointment1on1s );
            return $appointment1on1s[0];
        } else {
            return false;
        }
    }

    static function query_appointments( $criteria, $config = array() ) {

        global $birchschedule;

        if ( !is_array( $criteria ) ) {
            $criteria = array();
        }

        $default = array(
            'appointment_id' => -1,
            'client_id' => -1,

            'start' => time(),
            'end' => time() + 24 * 60 * 60,
            'location_id' => -1,
            'staff_id' => -1,
            'service_id' => -1,
            'status' => 'publish',
            'cache_results' => false
        );

        $criteria = array_merge( $default, $criteria );

        $start = $criteria['start'];
        $end = $criteria['end'];
        $location_id = $criteria['location_id'];
        $staff_id = $criteria['staff_id'];
        $service_id = $criteria['service_id'];
        $status = $criteria['status'];
        $cache_results = $criteria['cache_results'];

        if ( !is_array( $config ) || !$config ) {
            $config = array();
        }
        if ( isset( $config['appointment_keys'] ) ) {
            $appointment_keys = $config['appointment_keys'];
        } else {
            $appointment_keys = array();
        }
        if ( isset( $config['appointment1on1_keys'] ) ) {
            $appointment1on1_keys = $config['appointment1on1_keys'];
        } else {
            $appointment1on1_keys = array();
        }
        if ( isset( $config['client_keys'] ) ) {
            $client_keys = $config['client_keys'];
        } else {
            $client_keys = array();
        }

        $appointments_criteria = array(
            'post_type' => 'birs_appointment',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_birs_appointment_timestamp',
                    'value' => $start,
                    'compare' => '>=',
                    'type' => 'SIGNED'
                ),
                array(
                    'key' => '_birs_appointment_timestamp',
                    'value' => $end,
                    'compare' => '<=',
                    'type' => 'SIGNED'
                )
            ),
            'cache_results' => $cache_results
        );

        if ( $location_id != -1 ) {
            if ( !is_array( $location_id ) ) {
                $appointments_criteria['meta_query'][] = array(
                    'key' => '_birs_appointment_location',
                    'value' => $location_id,
                    'type' => 'UNSIGNED'
                );
            } else {
                $appointments_criteria['meta_query'][] = array(
                    'key' => '_birs_appointment_location',
                    'value' => $location_id,
                    'compare' => 'IN',
                    'type' => 'UNSIGNED'
                );
            }
        }
        if ( $staff_id != -1 ) {
            if ( !is_array( $staff_id ) ) {
                $appointments_criteria['meta_query'][] = array(
                    'key' => '_birs_appointment_staff',
                    'value' => $staff_id,
                    'type' => 'UNSIGNED'
                );
            } else {
                $appointments_criteria['meta_query'][] = array(
                    'key' => '_birs_appointment_staff',
                    'value' => $staff_id,
                    'compare' => 'IN',
                    'type' => 'UNSIGNED'
                );
            }
        }
        if ( $service_id != -1 ) {
            if ( !is_array( $service_id ) ) {
                $appointments_criteria['meta_query'][] = array(
                    'key' => '_birs_appointment_service',
                    'value' => $service_id,
                    'type' => 'UNSIGNED'
                );
            } else {
                $appointments_criteria['meta_query'][] = array(
                    'key' => '_birs_appointment_service',
                    'value' => $service_id,
                    'compare' => 'IN',
                    'type' => 'UNSIGNED'
                );
            }
        }

        $appointment_id = $criteria['appointment_id'];
        if ( $appointment_id != -1 ) {
            unset( $appointments_criteria['meta_query'] );
            if ( !is_array( $appointment_id ) ) {
                $appointments_criteria[] = array(
                    'p' => $appointment_id
                );
            } else {
                $appointments_criteria[] = array(
                    'post__in' => $appointment_id
                );
            }
        }

        if ( $appointment_id == -1 || $appointment_keys ) {
            $appointments = self::$birchschedule->model->query( $appointments_criteria,
                array(
                    'keys' => $appointment_keys
                )
            );
            $appointment_ids = array_keys( $appointments );
        } else {
            $appointment_ids = ( array )$appointment_id;
            $appointments = array();
            foreach ( $appointment_ids as $appointment_id ) {
                $appointments[$appointment_id] = array(
                    'appointment1on1s' => array()
                );
            }
        }

        $appointment1on1_keys = array_merge( $appointment1on1_keys,
            array( '_birs_appointment_id', '_birs_client_id', 'post_status' ) );
        $appointment1on1s_critera = array(
            'post_type' => 'birs_appointment1on1',
            'post_status' => $status,
            'meta_query' => array(
                array(
                    'key' => '_birs_appointment_id',
                    'value' => array_merge( $appointment_ids, array( 0 ) ),
                    'compare' => 'IN',
                    'type' => 'UNSIGNED'
                )
            ),
            'cache_results' => $cache_results
        );
        $client_id = $criteria['client_id'];
        if ( $client_id != -1 ) {
            if ( !is_array( $client_id ) ) {
                $appointment1on1s_critera['meta_query'][] = array(
                    'key' => '_birs_client_id',
                    'value' => $client_id,
                    'type' => 'UNSIGNED'
                );
            } else {
                $appointment1on1s_critera['meta_query'][] = array(
                    'key' => '_birs_client_id',
                    'value' => array_merge( $client_id, array( 0 ) ),
                    'compare' => 'IN',
                    'type' => 'UNSIGNED'
                );
            }
        }
        $appointment1on1s = self::$birchschedule->model->query( $appointment1on1s_critera,
            array(
                'keys' => $appointment1on1_keys
            )
        );
        $new_appointments = array();
        foreach ( $appointment1on1s as $appointment1on1_id => $appointment1on1 ) {
            $client_id = $appointment1on1['_birs_client_id'];
            if ( $client_keys ) {
                $client = self::$birchschedule->model->get( $client_id, array(
                        'keys' => $client_keys
                    ) );
            } else {
                $client = array();
            }
            $appointment1on1 = array_merge( $client, $appointment1on1 );
            if ( !isset( $client['_birs_client_name'] ) &&
                isset( $client['post_title'] ) ) {
                $appointment1on1['_birs_client_name'] = $client['post_title'];
            }

            $appointment_id = $appointment1on1['_birs_appointment_id'];
            $appointment = $appointments[$appointment_id];

            if ( !isset( $appointment['appointment1on1s'] ) ) {
                $appointment['appointment1on1s'] = array(
                    $appointment1on1_id => $appointment1on1
                );
            } else {
                $appointment['appointment1on1s'][$appointment1on1_id] = $appointment1on1;
            }
            $new_appointments[$appointment_id] = $appointment;
        }

        return $new_appointments;
    }

    static function if_cancel_appointment_outoftime( $appointment_id ) {
        global $birchpress;

        $time_before_cancel = self::$birchschedule->model->get_time_before_cancel();
        $appointment = self::$birchschedule->model->get( $appointment_id, array(
                'base_keys' => array(),
                'meta_keys' => array(
                    '_birs_appointment_timestamp'
                )
            ) );
        if ( !$appointment ) {
            return $birchpress->util->new_errors( 'appointment_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
        }
        if ( $appointment['_birs_appointment_timestamp'] - time() > $time_before_cancel * 60 * 60 ) {
            return true;
        } else {
            return false;
        }
    }

    static function if_reschedule_appointment_outoftime( $appointment_id ) {
        global $birchpress;

        $time_before_reschedule = self::$birchschedule->model->get_time_before_reschedule();
        $appointment = self::$birchschedule->model->get( $appointment_id, array(
                'base_keys' => array(),
                'meta_keys' => array(
                    '_birs_appointment_timestamp'
                )
            ) );
        if ( !$appointment ) {
            return $birchpress->util->new_errors( 'appointment_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
        }
        if ( $appointment['_birs_appointment_timestamp'] - time() > $time_before_reschedule * 60 * 60 ) {
            return true;
        } else {
            return false;
        }
    }

    static function if_appointment_cancelled( $appointment_id ) {
        $appointment1on1s = self::$package->get_appointment1on1s_by_appointment(
            $appointment_id,
            array(
                'status' => 'publish'
            )
        );
        if ( $appointment1on1s ) {
            return false;
        } else {
            return true;
        }
    }

    static function if_appointment1on1_cancelled( $appointment1on1_id ) {
        global $birchpress;

        $appointment1on1 = self::$birchschedule->model->get(
            $appointment1on1_id,
            array(
                'base_keys' => array( 'post_status' ),
                'meta_keys' => array(
                    '_birs_appointment_id'
                )
            )
        );
        if ( !$appointment1on1 ) {
            return $birchpress->util->new_errors( 'appointment_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
        }
        if ( $appointment1on1['post_status'] == 'cancelled' ) {
            return true;
        } else {
            return false;
        }
    }

    static function if_email_duplicated( $client_id, $email ) {
        global $birchschedule;

        $clients = $birchschedule->model->query(
            array(
                'post_type' => 'birs_client',
                'meta_query' => array(
                    array(
                        'key' => '_birs_client_email',
                        'value' => $email
                    )
                )
            ),
            array(
                'base_keys' => array(),
                'meta_keys' => array()
            )
        );
        if ( sizeof( $clients ) > 1 ) {
            return true;
        }
        elseif ( sizeof( $clients ) === 1 ) {
            $exist_client_id = array_shift( array_keys( $clients ) );
            return $client_id != $exist_client_id;
        } else {
            return false;
        }
    }

    static function save_client( $client_info ) {
        global $birchschedule;

        if ( isset( $client_info['_birs_client_fields'] ) ) {
            $fields = $client_info['_birs_client_fields'];
        } else {
            $fields = self::$birchschedule->model->get_client_fields();
        }
        $config = array(
            'meta_keys' => $fields,
            'base_keys' => array(
                'post_title'
            )
        );
        if ( isset( $client_info['_birs_client_email'] ) &&
            !isset( $client_info['ID'] ) ) {
            $email = $client_info['_birs_client_email'];
            $client = $birchschedule->model->get_client_by_email( $email,
                array(
                    'base_keys' => array(),
                    'meta_keys' => array()
                ) );
            if ( $client ) {
                $client_info['ID'] = $client['ID'];
            }
        }
        $client_info['post_type'] = 'birs_client';
        $client_id = $birchschedule->model->save( $client_info, $config );
        return $client_id;
    }

    static function get_user_by_staff( $staff_id ) {
        $staff = self::$birchschedule->model->get( $staff_id,
            array(
                'base_keys' => array(),
                'meta_keys' => array( '_birs_staff_email' )
            )
        );
        if ( !$staff ) {
            return false;
        }
        $user = get_user_by( 'email', $staff['_birs_staff_email'] );
        return $user;
    }

    static function get_appointment_capacity( $appointment_id ) {
        $appointment = self::$birchschedule->model->get( $appointment_id, array(
                'meta_keys' => array( '_birs_appointment_capacity' )
            ) );
        if ( isset( $appointment['_birs_appointment_capacity'] ) ) {
            $capacity = intval( $appointment['_birs_appointment_capacity'] );
            if ( $capacity < 1 ) {
                $capacity = 1;
            }
        } else {
            $capacity = 1;
        }
        return $capacity;
    }

    static function make_appointment( $appointment_info ) {
        birch_assert( isset( $appointment_info['_birs_appointment_location'] ) );
        birch_assert( isset( $appointment_info['_birs_appointment_service'] ) );
        birch_assert( isset( $appointment_info['_birs_appointment_staff'] ) );
        birch_assert( isset( $appointment_info['_birs_appointment_timestamp'] ) );
        $appointments = self::$birchschedule->model->query(
            array(
                'post_type' => 'birs_appointment',
                'post_status' => array( 'publish' ),
                'meta_query' => array(
                    array(
                        'key' => '_birs_appointment_location',
                        'value' => $appointment_info['_birs_appointment_location']
                    ),
                    array(
                        'key' => '_birs_appointment_service',
                        'value' => $appointment_info['_birs_appointment_service']
                    ),
                    array(
                        'key' => '_birs_appointment_staff',
                        'value' => $appointment_info['_birs_appointment_staff']
                    ),
                    array(
                        'key' => '_birs_appointment_timestamp',
                        'value' => $appointment_info['_birs_appointment_timestamp']
                    ),
                )
            ),
            array(
                'base_keys' => array(),
                'meta_keys' => array()
            )
        );
        if ( $appointments ) {
            $appointment_ids = array_keys( $appointments );
            return $appointment_ids[0];
        } else {
            $appointment_info['_birs_appointment_uid'] = uniqid( rand(), true );
            if ( !isset( $appointment_info['_birs_appointment_capacity'] ) ) {
                $appointment_info['_birs_appointment_capacity'] =
                    self::$birchschedule->model->get_service_capacity(
                    $appointment_info['_birs_appointment_service']
                );
            }
            if ( !isset( $appointment_info['_birs_appointment_duration'] ) ) {
                $appointment_info['_birs_appointment_duration'] =
                    self::$birchschedule->model->get_service_length(
                    $appointment_info['_birs_appointment_service']
                );
            }
            if ( !isset( $appointment_info['_birs_appointment_padding_before'] ) ) {
                $appointment_info['_birs_appointment_padding_before'] =
                    self::$birchschedule->model->get_service_padding_before(
                    $appointment_info['_birs_appointment_service']
                );
            }
            if ( !isset( $appointment_info['_birs_appointment_padding_after'] ) ) {
                $appointment_info['_birs_appointment_padding_after'] =
                    self::$birchschedule->model->get_service_padding_after(
                    $appointment_info['_birs_appointment_service']
                );
            }
        }
        $base_keys = array();
        $user = self::$package->get_user_by_staff( $appointment_info['_birs_appointment_staff'] );
        if ( $user ) {
            $appointment_info['post_author'] = $user->ID;
            $base_keys[] = 'post_author';
        }
        $config = array(
            'base_keys' => $base_keys,
            'meta_keys' => self::$birchschedule->model->get_appointment_fields()
        );
        $appointment_info['post_type'] = 'birs_appointment';
        $appointment_id = self::$birchschedule->model->save( $appointment_info, $config );
        self::remove_auto_draft_appointments();
        return $appointment_id;
    }

    static function remove_appointment_if_empty( $appointment_id ) {
        $appointment1on1s = self::$package->get_appointment1on1s_by_appointment( $appointment_id );
        if ( !$appointment1on1s ) {
            self::$birchschedule->model->delete( $appointment_id );
        }
    }

    static function remove_auto_draft_appointments() {
        global $birchpress;

        $appointments = $birchpress->db->query(
            array(
                'post_type' => 'birs_appointment',
                'post_status' => 'auto-draft'
            ),
            array(
                'base_keys' => array(),
                'meta_keys' => array()
            )
        );
        foreach ( $appointments as $appointment ) {
            $birchpress->db->delete( $appointment['ID'] );
        }
    }

    static function make_appointment1on1( $appointment1on1_info, $status = 'draft' ) {
        birch_assert( isset( $appointment1on1_info['_birs_client_id'] ), 'no client id' );
        if ( isset( $appointment1on1_info['_birs_appointment_id'] ) ) {
            $appointment_id = $appointment1on1_info['_birs_appointment_id'];
            $appointment = self::$birchschedule->model->get( $appointment_id, array(
                    'meta_keys' => array( '_birs_appointment_service' )
                ) );
            $appointment1on1_info['_birs_appointment_service'] = $appointment['_birs_appointment_service'];
        } else {
            birch_assert( isset( $appointment1on1_info['_birs_appointment_location'] ), 'no location' );
            birch_assert( isset( $appointment1on1_info['_birs_appointment_service'] ), 'no service' );
            birch_assert( isset( $appointment1on1_info['_birs_appointment_staff'] ), 'no staff' );
            birch_assert( isset( $appointment1on1_info['_birs_appointment_timestamp'] ), 'no timestamp' );
            $appointment_id = self::$package->make_appointment( $appointment1on1_info );
            $appointment1on1_info['_birs_appointment_id'] = $appointment_id;
        }
        $client_id = $appointment1on1_info['_birs_client_id'];
        $appointment1on1 = self::$package->get_appointment1on1( $appointment_id, $client_id );
        if ( $appointment1on1 ) {
            return $appointment1on1['ID'];
        }
        $appointment1on1_info['_birs_appointment1on1_uid'] = uniqid( rand(), true );
        if ( !isset( $appointment1on1_info['_birs_appointment1on1_price'] ) ) {
            $appointment1on1_info['_birs_appointment1on1_price'] =
                self::$birchschedule->model->get_service_price(
                $appointment1on1_info['_birs_appointment_service']
            );
        }
        $appointment1on1_info['_birs_appointment1on1_payment_status'] = 'not-paid';
        $appointment1on1_info['post_status'] = $status;
        if ( isset( $appointment1on1_info['_birs_appointment_fields'] ) ) {
            $custom_fields = $appointment1on1_info['_birs_appointment_fields'];
        } else {
            $custom_fields = self::$birchschedule->model->get_appointment1on1_custom_fields();
        }
        $std_fields = self::$birchschedule->model->get_appointment1on1_fields();
        $all_fields = array_merge( $std_fields, $custom_fields );
        $appointment1on1_info['post_type'] = 'birs_appointment1on1';
        $base_keys = array(
            'post_status'
        );
        $appointment1on1_id = self::$birchschedule->model->save( $appointment1on1_info, array(
                'base_keys' => $base_keys,
                'meta_keys' => $all_fields
            ) );
        return $appointment1on1_id;
    }

    static function change_appointment1on1_status( $appointment1on1_id, $status ) {
        global $birchpress;

        $appointment1on1_info = array(
            'ID' => $appointment1on1_id,
            'post_status' => $status,
            'post_type' => 'birs_appointment1on1'
        );
        $config = array(
            'base_keys' => array(
                'post_status'
            ),
            'meta_keys' => array()
        );
        $appointment1on1 = self::$birchschedule->model->get( $appointment1on1_id, $config );
        if ( !$appointment1on1 ) {
            return $birchpress->util->new_errors( 'appointment1on1_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
        }
        $old_status = $appointment1on1['post_status'];
        self::$birchschedule->model->save( $appointment1on1_info, $config );
        self::$package->do_change_appointment1on1_status( $appointment1on1_id, $status, $old_status );
        return true;
    }

    static function do_change_appointment1on1_status( $appointment1on1_id, $new_status, $old_status ) {
    }

    static function change_appointment1on1_custom_info( $appointment1on1_info ) {
        global $birchpress;

        $assertion = isset( $appointment1on1_info['ID'] ) ||
            ( isset( $appointment1on1_info['_birs_appointment_id'] ) &&
            isset( $appointment1on1_info['_birs_client_id'] ) );
        birch_assert( $assertion, 'ID or (_birs_appointment_id and _birs_client_id) should be in the info.' );
        if ( isset( $appointment1on1_info['_birs_appointment_fields'] ) ) {
            $custom_fields = $appointment1on1_info['_birs_appointment_fields'];
        } else {
            $custom_fields = self::$birchschedule->model->get_appointment1on1_custom_fields();
        }
        if ( !isset( $appointment1on1_info['ID'] ) ) {
            $appointment1on1 = self::$package->get_appointment1on1(
                $appointment1on1_info['_birs_appointment_id'],
                $appointment1on1_info['_birs_client_id']
            );
            if ( !$appointment1on1 ) {
                return $birchpress->util->new_errors( 'appointment1on1_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
            } else {
                $appointment1on1_info['ID'] = $appointment1on1['ID'];
            }
        }
        $appointment1on1_info['post_type'] = 'birs_appointment1on1';
        $appointment1on1_id = self::$birchschedule->model->save( $appointment1on1_info, array(
                'base_keys' => array(),
                'meta_keys' => $custom_fields
            ) );
        return $appointment1on1_id;
    }

    static function reschedule_appointment( $appointment_id, $appointment_info ) {
        birch_assert( isset( $appointment_info['_birs_appointment_staff'] ) ||
            isset( $appointment_info['_birs_appointment_timestamp'] ) );

        global $birchpress;

        $appointment1on1s = self::$package->get_appointment1on1s_by_appointment( $appointment_id,
            array(
                'status' => 'publish'
            )
        );
        if ( $appointment1on1s ) {
            foreach ( $appointment1on1s as $appointment1on1_id => $appointment1on1 ) {
                self::$package->reschedule_appointment1on1( $appointment1on1_id, $appointment_info );
            }
        }
    }

    static function do_reschedule_appointment1on1( $appointment1on1_id, $appointment_info ) {

    }

    static function reschedule_appointment1on1( $appointment1on1_id, $appointment_info ) {
        global $birchpress;

        birch_assert( isset( $appointment_info['_birs_appointment_staff'] ) ||
            isset( $appointment_info['_birs_appointment_timestamp'] ) );
        $appointment1on1 = self::$birchschedule->model->get( $appointment1on1_id, array(
                'base_keys' => array(),
                'meta_keys' => array(
                    '_birs_appointment_id',
                    '_birs_client_id'
                )
            ) );
        if ( !$appointment1on1 ) {
            return $birchpress->util->new_errors( 'appointment1on1_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
        }
        $appointment = self::$birchschedule->model->get( $appointment1on1['_birs_appointment_id'],
            array(
                'base_keys' => array(),
                'meta_keys' => array(
                    '_birs_appointment_location',
                    '_birs_appointment_service',
                    '_birs_appointment_staff',
                    '_birs_appointment_timestamp',
                    '_birs_appointment_duration',
                    '_birs_appointment_padding_before',
                    '_birs_appointment_padding_after'
                )
            )
        );
        if ( !$appointment ) {
            return $birchpress->util->new_errors( 'appointment_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
        }
        if ( !isset( $appointment_info['_birs_appointment_staff'] ) ) {
            $appointment_info['_birs_appointment_staff'] = $appointment['_birs_appointment_staff'];
        }
        if ( !isset( $appointment_info['_birs_appointment_timestamp'] ) ) {
            $appointment_info['_birs_appointment_timestamp'] = $appointment['_birs_appointment_timestamp'];
        }
        if ( !isset( $appointment_info['_birs_appointment_location'] ) ) {
            $appointment_info['_birs_appointment_location'] = $appointment['_birs_appointment_location'];
        }
        if ( !isset( $appointment_info['_birs_appointment_service'] ) ) {
            $appointment_info['_birs_appointment_service'] = $appointment['_birs_appointment_service'];
        }
        if ( $appointment['_birs_appointment_staff'] === $appointment_info['_birs_appointment_staff'] &&
            $appointment['_birs_appointment_timestamp'] === $appointment_info['_birs_appointment_timestamp'] &&
            $appointment['_birs_appointment_location'] === $appointment_info['_birs_appointment_location'] &&
            $appointment['_birs_appointment_service'] === $appointment_info['_birs_appointment_service'] ) {
            return false;
        }

        $appointment['_birs_appointment_staff'] = $appointment_info['_birs_appointment_staff'];
        $appointment['_birs_appointment_timestamp'] = $appointment_info['_birs_appointment_timestamp'];
        $appointment['_birs_appointment_location'] = $appointment_info['_birs_appointment_location'];
        $appointment['_birs_appointment_service'] = $appointment_info['_birs_appointment_service'];
        unset( $appointment['ID'] );
        $appointment_id = self::$package->make_appointment( $appointment );
        $orig_appointment_id = $appointment1on1['_birs_appointment_id'];
        $appointment1on1['_birs_appointment_id'] = $appointment_id;
        self::$birchschedule->model->save( $appointment1on1, array(
                'base_keys' => array(),
                'meta_keys' => array(
                    '_birs_appointment_id'
                )
            ) );
        $payments = self::$birchschedule->model->query(
            array(
                'post_type' => 'birs_payment',
                'meta_query' => array(
                    array(
                        'key' => '_birs_payment_appointment',
                        'value' => $orig_appointment_id
                    ),
                    array(
                        'key' => '_birs_payment_client',
                        'value' => $appointment1on1['_birs_client_id']
                    )
                )
            ),
            array(
                'meta_keys' => array(),
                'base_keys' => array()
            )
        );
        foreach ( $payments as $payment ) {
            $payment['_birs_payment_appointment'] = $appointment_id;
            self::$birchschedule->model->save( $payment, array(
                    'base_keys' => array(),
                    'meta_keys' => array(
                        '_birs_payment_appointment'
                    )
                ) );
        }
        self::$package->remove_appointment_if_empty( $orig_appointment_id );
        self::$package->do_reschedule_appointment1on1( $appointment1on1_id, $appointment_info );
        return true;
    }

    static function cancel_appointment( $appointment_id ) {
        $appointment1on1s = self::$package->get_appointment1on1s_by_appointment( $appointment_id,
            array(
                'status' => 'publish'
            )
        );
        if ( $appointment1on1s ) {
            foreach ( $appointment1on1s as $appointment1on1_id => $appointment1on1 ) {
                self::$package->cancel_appointment1on1( $appointment1on1_id );
            }
        }
    }

    static function cancel_appointment1on1( $appointment1on1_id ) {
        $appointment1on1 =
            self::$birchschedule->model->mergefields->get_appointment1on1_merge_values(
            $appointment1on1_id
        );
        if ( !$appointment1on1 ) {
            return false;
        } else {
            $appointment_id = $appointment1on1['_birs_appointment_id'];
            if ( $appointment1on1['post_status'] == 'cancelled' ) {
                return false;
            }
            $new_appointment1on1 = array(
                'post_status' => 'cancelled',
                'post_type' => 'birs_appointment1on1',
                'ID' => $appointment1on1_id
            );
            self::$birchschedule->model->save( $new_appointment1on1, array(
                    'base_keys' => array( 'post_status' ),
                    'meta_keys' => array()
                ) );
            return $appointment1on1;
        }
    }

    static function get_payments_by_appointment1on1( $appointment_id, $client_id ) {
        $payments = self::$birchschedule->model->query(
            array(
                'post_type' => 'birs_payment',
                'meta_query' => array(
                    array(
                        'key' => '_birs_payment_appointment',
                        'value' => $appointment_id
                    ),
                    array(
                        'key' => '_birs_payment_client',
                        'value' => $client_id
                    )
                )
            ),
            array(
                'meta_keys' => self::$birchschedule->model->get_payment_fields(),
                'base_keys' => array()
            )
        );
        return $payments;
    }

    static function make_payment( $payment_info ) {
        global $birchpress;

        birch_assert( isset( $payment_info['_birs_payment_appointment'] ), 'no _birs_payment_appointment' );
        birch_assert( isset( $payment_info['_birs_payment_client'] ), 'no _birs_payment_client' );
        birch_assert( isset( $payment_info['_birs_payment_amount'] ), 'no _birs_payment_amount' );
        birch_assert( isset( $payment_info['_birs_payment_type'] ), 'no _birs_payment_type' );
        birch_assert( isset( $payment_info['_birs_payment_trid'] ), 'no _birs_payment_trid' );
        birch_assert( isset( $payment_info['_birs_payment_timestamp'] ), 'no _birs_payment_timestamp' );
        birch_assert( isset( $payment_info['_birs_payment_currency'] ), 'no _birs_payment_currency' );
        $appointment_id = $payment_info['_birs_payment_appointment'];
        $client_id = $payment_info['_birs_payment_client'];
        $appointment1on1 = self::$package->get_appointment1on1(
            $appointment_id,
            $client_id,
            array(
                'meta_keys' => array(
                    '_birs_appointment1on1_price'
                ),
                'base_keys' => array()
            )
        );
        if ( !$appointment1on1 ) {
            return $birchpress->util->new_errors( 'appointment1on1_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
        }
        $config = array(
            'meta_keys' => self::$birchschedule->model->get_payment_fields(),
            'base_keys' => array()
        );
        $payment_info['post_type'] = 'birs_payment';
        $payment_id = self::$birchschedule->model->save( $payment_info, $config );
        $appointment_price = $appointment1on1['_birs_appointment1on1_price'];
        $all_payments = self::$package->get_payments_by_appointment1on1( $appointment_id, $client_id );
        $paid = 0;
        foreach ( $all_payments as $payment_id => $payment ) {
            $paid += $payment['_birs_payment_amount'];
        }
        $payment_status = 'not-paid';
        if ( $paid > 0 && $appointment_price - $paid >= 0.01 ) {
            $payment_status = 'partially-paid';
        }
        if ( $paid > 0 && $appointment_price - $paid < 0.01 ) {
            $payment_status = 'paid';
        }
        $appointment1on1['_birs_appointment1on1_payment_status'] = $payment_status;
        self::$birchschedule->model->save( $appointment1on1, array(
                'base_keys' => array(),
                'meta_keys' => array( '_birs_appointment1on1_payment_status' )
            ) );
        return $payment_id;
    }

    static function get_payment_types() {
        return array(
            'credit_card' => __( 'Credit Card', 'birchschedule' ),
            'cash' => __( 'Cash', 'birchschedule' )
        );
    }

    static function delete_appointment1on1( $appointment1on1_id ) {
        $fields = self::$birchschedule->model->get_appointment1on1_fields();
        $custom_fields = self::$birchschedule->model->get_appointment1on1_custom_fields();
        $fields = array_merge( $fields, $custom_fields );
        foreach ( $fields as $field ) {
            delete_post_meta( $appointment1on1_id, $field );
        }
        wp_delete_post( $appointment1on1_id, true );
    }

    static function delete_appointment( $appointment_id ) {
        $appointment1on1s =
            self::$package->get_appointment1on1s_by_appointment( $appointment_id, array( 'status' => 'any' ) );
        foreach ( $appointment1on1s as $appointment1on1_id => $appointment1on1 ) {
            self::$package->delete_appointment1on1( $appointment1on1_id );
        }
        $fields = self::$birchschedule->model->get_appointment_fields();
        foreach ( $fields as $field ) {
            delete_post_meta( $appointment_id, $field );
        }
        wp_delete_post( $appointment_id, true );
    }

    static function delete_appointments( $start, $end, $location_id, $staff_id ) {
        $criteria = array(
            'start' => $start,
            'end' => $end,
            'location_id' => $location_id,
            'staff_id' => $staff_id
        );
        $appointments =
            self::$package->query_appointments( $criteria );
        foreach ( $appointments as $appointment_id => $appointment ) {
            self::$package->delete_appointment( $appointment_id );
        }
    }

    static function record_fully_booked_day( $staff_id, $location_id,
        $service_id, $date, $time_options ) {

        $empty = true;
        foreach ( $time_options as $key => $value ) {
            if ( $value['avaliable'] ) {
                $empty = false;
                break;
            }
        }
        $date_text = $date->format( 'Y-m-d' );
        if ( $empty ) {
            self::$birchschedule->model->schedule->mark_fully_booked_day(
                $staff_id, $location_id, $service_id, $date_text );
        } else {
            self::$birchschedule->model->schedule->unmark_fully_booked_day(
                $staff_id, $location_id, $service_id, $date_text );
        }
    }

    static function asyn_check_if_fully_booked( $appointment1on1_id ) {
        global $birchpress;

        $appointment1on1 =
            self::$birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );
        $staff_id = $appointment1on1['_birs_appointment_staff'];
        $timestamp = $appointment1on1['_birs_appointment_timestamp'];
        $date = $birchpress->util->get_wp_datetime( $timestamp );
        $date_text = $date->format( 'Y-m-d' );

        wp_remote_post( admin_url( 'admin-post.php' ), array(
                'method' => 'POST',
                'blocking' => false,
                'body' => array(
                    'birs_appointment_staff' => $staff_id,
                    'birs_appointment_date' => $date_text,
                    'action' => 'birchschedule_model_booking_check_if_fully_booked'
                )
            ) );
    }

    static function admin_post_check_if_fully_booked() {
        $staff_id = $_POST['birs_appointment_staff'];
        $date_text = $_POST['birs_appointment_date'];

        self::$package->check_if_fully_booked( $staff_id, $date_text );
    }

    static function check_if_fully_booked( $staff_id, $date_text ) {
        global $birchpress;

        $services = self::$birchschedule->model->get_services_by_staff( $staff_id );
        $locations = self::$birchschedule->model->get_locations_listing_order();
        foreach ( $services as $service_id => $value ) {
            foreach ( $locations as $location_id ) {
                $date = $birchpress->util->get_wp_datetime( "$date_text 00:00:00" );

                $time_options = self::$birchschedule->model->schedule->get_staff_avaliable_time(
                    $staff_id, $location_id, $service_id, $date
                );
            }
        }
    }

    static function async_recheck_fully_booked_days() {
        wp_remote_post( admin_url( 'admin-post.php' ), array(
                'method' => 'POST',
                'blocking' => false,
                'body' => array(
                    'action' => 'birchschedule_model_booking_recheck_fully_booked_days'
                )
            ) );
    }

    static function admin_post_recheck_fully_booked_days() {
        $fully_booked = self::$birchschedule->model->schedule->get_fully_booked_days();
        foreach ( $fully_booked as $date_text => $staff_arr ) {
            foreach ( $staff_arr as $staff_id => $arr ) {
                self::$package->check_if_fully_booked( $staff_id, $date_text );
            }
        }
    }
}

Birchschedule_Model_Booking_Imp::init_vars();
