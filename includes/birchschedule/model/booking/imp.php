<?php

final class Birchschedule_Model_Booking_Imp {

    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->model->booking;
    }

	static function init() {}

    static function get_appointment_title($appointment_id) {
        global $birchschedule;
        birch_assert($birchschedule->model->is_valid_id($appointment_id));

        $appointment = $birchschedule->model->get($appointment_id, 
            array(
                'base_keys' => array(),
                'meta_keys' => array(
                    '_birs_appointment_service'
                )
            )
        );
        $clients = self::$package->get_appointment_clients($appointment_id, 
            array(
                'base_keys' => array('post_title'),
                'meta_keys' => array()
            ), 
            array('publish'));
        $service = $birchschedule->model->get($appointment['_birs_appointment_service'],
            array(
                'base_keys' => array('post_title'),
                'meta_keys' => array()
            ));
        if(sizeof($clients) > 1) {
            $title = $service['post_title'] . ' - ' . sprintf(__('%s Clients', 'birchschedule'), sizeof($clients));
        } 
        else if(sizeof($clients) == 1){
            $clients = array_values($clients);
            $title = $service['post_title'] . ' - ' . $clients[0]['post_title'];
        } 
        else {
            $title = $service['post_title'];
        }
        return $title;
        
    }

    static function get_appointment1on1(
            $appointment_id, $client_id, 
            $config = false, $post_status = 'any'
        ) {
        global $birchschedule;

        if($config === false) {
            $fields = $birchschedule->model->get_appointment1on1_custom_fields();
            $fields = array_merge($fields, array(
                '_birs_appointment_client', '_birs_appointment_id'
            ));
            $config = array(
                'base_keys' => array(
                    'post_status'
                ),
                'meta_keys' => $fields
            );
        }
        $appointment1on1s = $birchschedule->model->query(
            array(
                'post_type' => 'birs_appointment1on1',
                'meta_query' => array(
                    array(
                        'key' => '_birs_client_id',
                        'value' => $client_id
                    ),
                    array(
                        'key' => '_birs_appointment_id',
                        'value' => $appointment_id
                    )
                ),
                'post_status' => $post_status
            ), 
            $config
        );
        if($appointment1on1s) {
            $appointment1on1s = array_values($appointment1on1s);
            return $appointment1on1s[0];
        } else {
            return false;
        }
    }

    static function _filter_appointments($appointments) {
        $new_appointments = array();
        foreach($appointments as $appointment) {
            $appointment1on1s = 
                self::$package->get_appointment1on1s_by_appointment(
                    $appointment['ID'],
                    array(
                        'base_keys' => array(),
                        'meta_keys' => array()
                    ),
                    'publish'
                );
            if($appointment1on1s) {
                $appointment['post_title'] = 
                    self::$birchschedule->model->booking->get_appointment_title($appointment['ID']);
                $new_appointments[$appointment['ID']] = $appointment;
            }
        }
        return $new_appointments;
    }

    static function query_appointments($start, $end, $location_id, 
        $staff_id, $config = array(), $filter = false) {
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
        if($filter === false) {
            $filter = array(__CLASS__, '_filter_appointments');
        }
        $new_appointments = call_user_func($filter, $appointments);
        return $new_appointments;
    }

    static function if_cancel_appointment_outoftime($appointment_id) {
        global $birchpress;

        $time_before_cancel = self::$birchschedule->model->get_time_before_cancel();
        $appointment = self::$birchschedule->model->get($appointment_id, array(
            'base_keys' => array(),
            'meta_keys' => array(
                '_birs_appointment_timestamp'
            )
        ));
        if(!$appointment) {
            return $birchpress->util->new_errors('appointment_nonexist', __('The appointment does not exist.', 'birchschedule'));
        }
        if($appointment['_birs_appointment_timestamp'] - time() > $time_before_cancel * 60 * 60) {
            return true;
        } else {
            return false;
        }
    }

    static function if_reschedule_appointment_outoftime($appointment_id) {
        global $birchpress;

        $time_before_reschedule = self::$birchschedule->model->get_time_before_reschedule();
        $appointment = self::$birchschedule->model->get($appointment_id, array(
            'base_keys' => array(),
            'meta_keys' => array(
                '_birs_appointment_timestamp'
            )
        ));
        if(!$appointment) {
            return $birchpress->util->new_errors('appointment_nonexist', __('The appointment does not exist.', 'birchschedule'));
        }
        if($appointment['_birs_appointment_timestamp'] - time() > $time_before_reschedule * 60 * 60) {
            return true;
        } else {
            return false;
        }
    }

    static function if_appointment_cancelled($appointment_id) {
        $appointment1on1s = self::$package->get_appointment1on1s_by_appointment(
            $appointment_id,
            array(
                'base_keys' => array(),
                'meta_keys' => array()
            ),
            array('publish')
        );
        if($appointment1on1s) {
            return false;
        } else {
            return true;
        }
    }

    static function if_appointment1on1_cancelled($appointment1on1_id) {
        $appointment1on1 = self::$birchschedule->model->get(
            $appointment1on1_id, 
            array(
                'base_keys' => array('post_status'),
                'meta_keys' => array(
                    '_birs_appointment_id'
                )
            )
        );
        if(!$appointment1on1) {
            return $birchpress->util->new_errors('appointment_nonexist', __('The appointment does not exist.', 'birchschedule'));
        }
        if($appointment1on1['post_status'] == 'cancelled') {
            return true;
        } else {
            return false;
        }
    }

    static function if_email_duplicated($client_id, $email) {
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
        if(sizeof($clients) > 1) {
            return true;
        }
        elseif(sizeof($clients) === 1) {
            $exist_client_id = array_shift(array_keys($clients));
            return $client_id != $exist_client_id;
        } else {
            return false;
        }        
    }

    static function save_client($client_info) {
        global $birchschedule;

        if (isset($client_info['_birs_client_fields'])) {
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
        if(isset($client_info['_birs_client_email']) && 
            !isset($client_info['ID'])) {
            $email = $client_info['_birs_client_email'];
            $client = $birchschedule->model->get_client_by_email($email, 
                array(
                    'base_keys' => array(),
                    'meta_keys' => array()
                ));
            if($client) {
                $client_info['ID'] = $client['ID'];
            }
        }
        $client_info['post_type'] = 'birs_client';
        $client_id = $birchschedule->model->save($client_info, $config);
        return $client_id;
    }

    static function get_appointment1on1s_by_appointment($appointment_id, $config, $status) {
        $appointment1on1s = self::$birchschedule->model->query(
            array(
                'post_type' => 'birs_appointment1on1',
                'post_status' => $status,
                'meta_query' => array(
                    array(
                        'key' => '_birs_appointment_id',
                        'value' => $appointment_id
                    )
                )
            ), 
            $config
        );
        return $appointment1on1s;
    }

    static function get_appointment_clients($appointment_id, $config, $status) {
        $appointment1on1s = self::$package->get_appointment1on1s_by_appointment($appointment_id, 
            array(
                'base_keys' => array(),
                'meta_keys' => array('_birs_client_id')
            ), 
            $status
        );
        $clients = array();
        if($appointment1on1s) {
            foreach($appointment1on1s as $appointment1on1) {
                $client = self::$birchschedule->model->get($appointment1on1['_birs_client_id'],
                    $config);
                if($client) {
                    $clients[$client['ID']] = $client;
                }
            }
        }
        return $clients;
    }

    static function get_user_by_staff($staff_id) {
        $staff = self::$birchschedule->model->get($staff_id, 
            array(
                'base_keys' => array(),
                'meta_keys' => array('_birs_staff_email')
            )
        );
        if(!$staff) {
            return false;
        }
        $user = get_user_by('email', $staff['_birs_staff_email']);
        return $user;
    }

    static function get_appointment_capacity($appointment_id) {
        $appointment = self::$birchschedule->model->get($appointment_id, array(
            'meta_keys' => array('_birs_appointment_capacity')
        ));
        if(isset($appointment['_birs_appointment_capacity'])) {
            $capacity = intval($appointment['_birs_appointment_capacity']);
            if($capacity < 1) {
                $capacity = 1;
            }
        } else {
            $capacity = 1;
        }
        return $capacity;
    }

    static function make_appointment($appointment_info) {
        birch_assert(isset($appointment_info['_birs_appointment_location']));
        birch_assert(isset($appointment_info['_birs_appointment_service']));
        birch_assert(isset($appointment_info['_birs_appointment_staff']));
        birch_assert(isset($appointment_info['_birs_appointment_timestamp']));
        $appointments = self::$birchschedule->model->query(
            array(
                'post_type' => 'birs_appointment',
                'post_status' => array('publish'),
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
        if($appointments) {
            $appointment_ids = array_keys($appointments);
            return $appointment_ids[0];
        } else {
            $appointment_info['_birs_appointment_uid'] = uniqid(rand(), true);
            if(!isset($appointment_info['_birs_appointment_capacity'])) {
                $appointment_info['_birs_appointment_capacity'] = 
                    self::$birchschedule->model->get_service_capacity(
                        $appointment_info['_birs_appointment_service']
                    );
            }
            if(!isset($appointment_info['_birs_appointment_duration'])) {
                $appointment_info['_birs_appointment_duration'] = 
                    self::$birchschedule->model->get_service_length(
                        $appointment_info['_birs_appointment_service']
                    );
            }
            if(!isset($appointment_info['_birs_appointment_padding_before'])) {
                $appointment_info['_birs_appointment_padding_before'] =
                    self::$birchschedule->model->get_service_padding_before(
                        $appointment_info['_birs_appointment_service']
                    );
            }
            if(!isset($appointment_info['_birs_appointment_padding_after'])) {
                $appointment_info['_birs_appointment_padding_after'] =
                    self::$birchschedule->model->get_service_padding_after(
                        $appointment_info['_birs_appointment_service']
                    );
            }
        }
        $base_keys = array();
        $user = self::$package->get_user_by_staff($appointment_info['_birs_appointment_staff']);
        if($user) {
            $appointment_info['post_author'] = $user->ID;
            $base_keys[] = 'post_author';
        }
        $config = array(
            'base_keys' => $base_keys,
            'meta_keys' => self::$birchschedule->model->get_appointment_fields()
        );
        $appointment_info['post_type'] = 'birs_appointment';
        $appointment_id = self::$birchschedule->model->save($appointment_info, $config);
        self::remove_auto_draft_appointments();
        return $appointment_id;
    }

    static function remove_appointment_if_empty($appointment_id) {
        $appointment1on1s = self::$package->get_appointment1on1s_by_appointment(
            $appointment_id,
            array(
                'base_keys' => array(),
                'meta_keys' => array()
            ),
            'any'
        );
        if(!$appointment1on1s) {
            self::$birchschedule->model->delete($appointment_id);
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
        foreach($appointments as $appointment) {
            $birchpress->db->delete($appointment['ID']);
        }
    }

    static function schedule_appointment1on1($appointment1on1_info, $status = 'draft') {
        birch_assert(isset($appointment1on1_info['_birs_client_id']), 'no client id');
        if(isset($appointment1on1_info['_birs_appointment_id'])) {
            $appointment_id = $appointment1on1_info['_birs_appointment_id'];
            $appointment = self::$birchschedule->model->get($appointment_id, array(
                'meta_keys' => array('_birs_appointment_service')
            ));
            $appointment1on1_info['_birs_appointment_service'] = $appointment['_birs_appointment_service'];
        } else {
            birch_assert(isset($appointment1on1_info['_birs_appointment_location']), 'no location');
            birch_assert(isset($appointment1on1_info['_birs_appointment_service']), 'no service');
            birch_assert(isset($appointment1on1_info['_birs_appointment_staff']), 'no staff');
            birch_assert(isset($appointment1on1_info['_birs_appointment_timestamp']), 'no timestamp');
            $appointment_id = self::$package->make_appointment($appointment1on1_info);
            $appointment1on1_info['_birs_appointment_id'] = $appointment_id;
        }
        $client_id = $appointment1on1_info['_birs_client_id'];
        $appointment1on1 = self::$package->get_appointment1on1($appointment_id, $client_id);
        if($appointment1on1) {
            return $appointment1on1['ID'];
        }
        $appointment1on1_info['_birs_appointment1on1_uid'] = uniqid(rand(), true);
        if(!isset($appointment1on1_info['_birs_appointment1on1_price'])) {
            $appointment1on1_info['_birs_appointment1on1_price'] =
                self::$birchschedule->model->get_service_price(
                    $appointment1on1_info['_birs_appointment_service']
                );
        }
        $appointment1on1_info['_birs_appointment1on1_payment_status'] = 'not-paid';
        $appointment1on1_info['post_status'] = $status;
        if (isset($appointment1on1_info['_birs_appointment_fields'])) {
            $custom_fields = $appointment1on1_info['_birs_appointment_fields'];
        } else {
            $custom_fields = self::$birchschedule->model->get_appointment1on1_custom_fields();
        }
        $std_fields = self::$birchschedule->model->get_appointment1on1_fields();
        $all_fields = array_merge($std_fields, $custom_fields);
        $appointment1on1_info['post_type'] = 'birs_appointment1on1';
        $base_keys = array(
            'post_status'
        );
        $appointment1on1_id = self::$birchschedule->model->save($appointment1on1_info, array(
            'base_keys' => $base_keys,
            'meta_keys' => $all_fields
        ));
        return $appointment1on1_id;
    }

    static function change_appointment1on1_status($appointment1on1_id, $status) {
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
        $appointment1on1 = self::$birchschedule->model->get($appointment1on1_id, $config);
        if(!$appointment1on1) {
            return $birchpress->util->new_errors('appointment1on1_nonexist', __('The appointment does not exist.', 'birchschedule'));
        }
        $old_status = $appointment1on1['post_status'];
        self::$birchschedule->model->save($appointment1on1_info, $config);
        self::$package->do_change_appointment1on1_status($appointment1on1_id, $status, $old_status);
        return true;
    }

    static function do_change_appointment1on1_status($appointment1on1_id, $new_status, $old_status) {
    }

    static function change_appointment1on1_custom_info($appointment1on1_info) {
        global $birchpress;

        $assertion = isset($appointment1on1_info['ID']) || 
            (isset($appointment1on1_info['_birs_appointment_id']) && 
                isset($appointment1on1_info['_birs_client_id']));
        birch_assert($assertion, 'ID or (_birs_appointment_id and _birs_client_id) should be in the info.');
        if (isset($appointment1on1_info['_birs_appointment_fields'])) {
            $custom_fields = $appointment1on1_info['_birs_appointment_fields'];
        } else {
            $custom_fields = self::$birchschedule->model->get_appointment1on1_custom_fields();
        }
        if(!isset($appointment1on1_info['ID'])) {
            $appointment1on1 = self::$package->get_appointment1on1(
                $appointment1on1_info['_birs_appointment_id'],
                $appointment1on1_info['_birs_client_id']
            );
            if(!$appointment1on1) {
                return $birchpress->util->new_errors('appointment1on1_nonexist', __('The appointment does not exist.', 'birchschedule'));
            } else {
                $appointment1on1_info['ID'] = $appointment1on1['ID'];
            }
        }
        $appointment1on1_info['post_type'] = 'birs_appointment1on1';
        $appointment1on1_id = self::$birchschedule->model->save($appointment1on1_info, array(
            'base_keys' => array(),
            'meta_keys' => $custom_fields
        ));
        return $appointment1on1_id;
    }

    static function reschedule_appointment($appointment_id, $appointment_info) {
        birch_assert(isset($appointment_info['_birs_appointment_staff']) || 
            isset($appointment_info['_birs_appointment_timestamp']));

        global $birchpress;
        
        $appointment1on1s = self::$package->get_appointment1on1s_by_appointment($appointment_id,
            array(
                'base_keys' => array(),
                'meta_keys' => array()
            ),
            array('publish')
        );
        if($appointment1on1s) {
            foreach($appointment1on1s as $appointment1on1_id => $appointment1on1) {
                self::$package->reschedule_appointment1on1($appointment1on1_id, $appointment_info);
            }
        }
        self::$package->remove_appointment_if_empty($appointment_id);
    }

    static function do_reschedule_appointment1on1($appointment1on1_id, $appointment_info) {

    }

    static function reschedule_appointment1on1($appointment1on1_id, $appointment_info) {
        global $birchpress;
        
        birch_assert(isset($appointment_info['_birs_appointment_staff']) || 
            isset($appointment_info['_birs_appointment_timestamp']));
        $appointment1on1 = self::$birchschedule->model->get($appointment1on1_id, array(
            'base_keys' => array(),
            'meta_keys' => array(
                '_birs_appointment_id'
            )
        ));
        if(!$appointment1on1) {
            return $birchpress->util->new_errors('appointment1on1_nonexist', __('The appointment does not exist.', 'birchschedule'));
        }
        $appointment = self::$birchschedule->model->get($appointment1on1['_birs_appointment_id'], 
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
        if(!$appointment) {
            return $birchpress->util->new_errors('appointment_nonexist', __('The appointment does not exist.', 'birchschedule'));
        }
        if(!isset($appointment_info['_birs_appointment_staff'])) {
            $appointment_info['_birs_appointment_staff'] = $appointment['_birs_appointment_staff'];
        }
        if(!isset($appointment_info['_birs_appointment_timestamp'])) {
            $appointment_info['_birs_appointment_timestamp'] = $appointment['_birs_appointment_timestamp'];
        }
        if($appointment['_birs_appointment_staff'] === $appointment_info['_birs_appointment_staff'] &&
            $appointment['_birs_appointment_timestamp'] === $appointment_info['_birs_appointment_timestamp']) {
            return false;
        }

        $appointment['_birs_appointment_staff'] = $appointment_info['_birs_appointment_staff'];
        $appointment['_birs_appointment_timestamp'] = $appointment_info['_birs_appointment_timestamp'];
        unset($appointment['ID']);
        $appointment_id = self::$package->make_appointment($appointment);
        $orig_appointment_id = $appointment1on1['_birs_appointment_id'];
        $appointment1on1['_birs_appointment_id'] = $appointment_id;
        self::$birchschedule->model->save($appointment1on1, array(
            'base_keys' => array(),
            'meta_keys' => array(
                '_birs_appointment_id'
            )
        ));
        self::$package->remove_appointment_if_empty($orig_appointment_id);
        self::$package->do_reschedule_appointment1on1($appointment1on1_id, $appointment_info);
        return true;
    }

    static function cancel_appointment($appointment_id) {
        $appointment1on1s = self::$package->get_appointment1on1s_by_appointment($appointment_id,
            array(
                'base_keys' => array(),
                'meta_keys' => array()
            ),
            array('publish')
        );
        if($appointment1on1s) {
            foreach($appointment1on1s as $appointment1on1_id => $appointment1on1) {
                self::$package->cancel_appointment1on1($appointment1on1_id);
            }
        }
        self::$birchschedule->model->delete($appointment_id);
    }

    static function cancel_appointment1on1($appointment1on1_id) {
        $appointment1on1 = 
            self::$birchschedule->model->mergefields->get_appointment1on1_merge_values(
                $appointment1on1_id
            );
        if(!$appointment1on1) {
                return false;
        } else {
            $appointment_id = $appointment1on1['_birs_appointment_id'];
            if($appointment1on1['post_status'] == 'cancelled') {
                return false;
            }
            $new_appointment1on1 = array(
                'post_status' => 'cancelled',
                'post_type' => 'birs_appointment1on1',
                'ID' => $appointment1on1_id
            );
            self::$birchschedule->model->save($new_appointment1on1, array(
                'base_keys' => array('post_status'),
                'meta_keys' => array()
            ));
            self::$package->remove_appointment_if_empty($appointment_id);
            return $appointment1on1;
        }
    }

    static function get_payments_by_appointment1on1($appointment_id, $client_id) {
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

    static function make_payment($payment_info) {
        global $birchpress;

        birch_assert(isset($payment_info['_birs_payment_appointment']), 'no _birs_payment_appointment');
        birch_assert(isset($payment_info['_birs_payment_client']), 'no _birs_payment_client');
        birch_assert(isset($payment_info['_birs_payment_amount']), 'no _birs_payment_amount');
        birch_assert(isset($payment_info['_birs_payment_type']), 'no _birs_payment_type');
        birch_assert(isset($payment_info['_birs_payment_trid']), 'no _birs_payment_trid');
        birch_assert(isset($payment_info['_birs_payment_timestamp']), 'no _birs_payment_timestamp');
        birch_assert(isset($payment_info['_birs_payment_currency']), 'no _birs_payment_currency');
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
        if(!$appointment1on1) {
            return $birchpress->util->new_errors('appointment1on1_nonexist', __('The appointment does not exist.', 'birchschedule'));
        }
        $config = array(
            'meta_keys' => self::$birchschedule->model->get_payment_fields(),
            'base_keys' => array()
        );
        $payment_info['post_type'] = 'birs_payment';
        $payment_id = self::$birchschedule->model->save($payment_info, $config);
        $appointment_price = $appointment1on1['_birs_appointment1on1_price'];
        $all_payments = self::$package->get_payments_by_appointment1on1($appointment_id, $client_id);
        $paid = 0;
        foreach($all_payments as $payment_id => $payment) {
            $paid += $payment['_birs_payment_amount'];
        }
        $payment_status = 'not-paid';
        if($paid > 0 && $appointment_price - $paid >= 0.01) {
            $payment_status = 'partially-paid';
        }
        if($paid > 0 && $appointment_price - $paid < 0.01) {
            $payment_status = 'paid';
        }
        $appointment1on1['_birs_appointment1on1_payment_status'] = $payment_status;
        self::$birchschedule->model->save($appointment1on1, array(
            'base_keys' => array(),
            'meta_keys' => array('_birs_appointment1on1_payment_status')
        ));
        return $payment_id;
    }

    static function get_payment_types() {
        return array(
            'credit_card' => __('Credit Card', 'birchschedule'),
            'cash' => __('Cash', 'birchschedule')
        );
    }

}

Birchschedule_Model_Booking_Imp::init_vars();
