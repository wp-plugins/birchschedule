<?php

class BIRS_Calendar_Logic {
	private static $instance;

	static function get_instance() {
		if(!self::$instance) {
			self::$instance = new BIRS_Calendar_Logic();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action('init', array($this, 'init'));
		add_action('admin_init', array($this, 'admin_init'));
	}

	function init() {
        add_filter('birchschedule_location_map', array($this, 'get_location_map'));
        add_filter('birchschedule_location_service_map', array($this, 'get_location_service_map'));
        add_filter('birchschedule_location_staff_map_admin', array($this, 'get_location_staff_map'), 10, 2);
        add_filter('birchschedule_location_staff_map', array($this, 'get_location_staff_map'), 10, 2);
        add_filter('birchschedule_location_listing_order', array($this, 'get_location_listing_order'), 10, 2);
        add_filter('birchschedule_staff_listing_order', array($this, 'get_staff_listing_order'), 10, 2);
        add_filter('birchschedule_service_listing_order', array($this, 'get_service_listing_order'));
        add_filter('birchschedule_validate_booking_form_info', array($this, 'validate_booking_form'));
        add_filter('birchschedule_save_appointment_frontend', array($this, 'save_appointment'));
        add_filter('birchschedule_service_option_text', 
            array($this, 'get_service_option_text'), 10, 2);
	}

	function admin_init() {
        add_filter('birchschedule_validate_booking_info_admin', array($this, 'validate_booking_info_admin'));
	}

    function query_appointments($start, $end, $location_id, $staff_id) {
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
        $query = new BIRS_Model_Query(array(
                    'post_type' => 'birs_appointment',
                    'nopaging' => true,
                    'meta_query' => $meta_query
                ), array(
                    'base_keys' => array('post_title'),
                    'meta_keys' => array('_birs_appointment_service', '_birs_appointment_client')
                ));
        $appointments = $query->query();
        $new_appointments = array();
        foreach($appointments as $appointment) {
            $service = new BIRS_Service($appointment['_birs_appointment_service'], array(
                        'base_keys' => array(
                            'post_title'
                        )
                    ));
            $service->load();
            $client = new BIRS_Client($appointment['_birs_appointment_client'], array(
                        'base_keys' => array(
                            'post_title'
                        )
                    ));
            $client->load();
            $appointment['post_title'] = $service['post_title'] . ' - ' . $client['post_title'];
            $new_appointments[] = $appointment->get_data();
        }
        return $new_appointments;
    }

    function get_service_option_text($text, $service) {
        return $service->post_title;
    }	

    function get_all_schedule() {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_staff'
                        ),
                        array(
                            'meta_keys' => array(
                                '_birs_staff_schedule'
                            )
                ));
        $staff = $query->query();
        $allschedule = array();
        foreach (array_values($staff) as $thestaff) {
            $schedule = $thestaff->get_all_calculated_schedule();
            $allschedule[$thestaff->ID] = $schedule;
        }
        return $allschedule;
    }
    
    function get_all_dayoffs() {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_staff'
                        ),
                        array(
                            'meta_keys' => array(
                                '_birs_staff_dayoffs'
                            )
                ));
        $staff = $query->query();
        $dayoffs = array();
        foreach (array_values($staff) as $thestaff) {
            $dayoffs[$thestaff->ID] = 
                apply_filters('birchschedule_staff_days_off', 
                    $thestaff->_birs_staff_dayoffs, $thestaff->ID);
        }
        return $dayoffs;
    }

    function get_service_price_map() {
        $query = new BIRS_Model_Query(
                        array('post_type' => 'birs_service'),
                        array(
                            'meta_keys' => array('_birs_service_price', '_birs_service_price_type'))
        );
        $services = $query->query();
        $price_map = array();
        foreach ($services as $service) {
            $price_map[$service['ID']] = array(
                'price' => $service['_birs_service_price'],
                'price_type' => $service['_birs_service_price_type']
            );
        }
        return $price_map;
    }
    
    function get_service_duration_map() {
        $query = new BIRS_Model_Query(
                        array('post_type' => 'birs_service'),
                        array(
                            'meta_keys' => array(
                                '_birs_service_length',
                                '_birs_service_length_type'
                            )
                        )
        );
        $services = $query->query();
        $duration_map = array();
        foreach ($services as $service) {
            $duration_map[$service['ID']] = array(
                'duration' => $service->get_service_length()
            );
        }
        return $duration_map;
    }

    function get_location_map() {
        $map = array();
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_location',
                            'order' => 'ASC',
                            'orderby' => 'title'
                        ),
                        array(
                            'base_keys' => array(
                                'post_title'
                            )
                        )
        );
        $locations = $query->query();
        foreach($locations as $location_id => $location) {
            $map[$location_id] = $location->get_data();
        }
        $map[-1] = array(
            'post_title' => __('[All]', 'birchschedule')
        );
        return $map;
    }
    
    function get_location_service_map() {
        $map = array();
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_location'
                        ),
                        array()
        );
        $locations = $query->query();
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_service',
                            'order' => 'ASC',
                            'orderby' => 'title'
                        ),
                        array(
                            'base_keys' => array(
                                'post_title'
                            )
                        )
        );
        $services = $query->query();
        $service_map = array();
        foreach($services as $service_id => $service) {
            $service_map[$service_id] = $service->post_title;
        }
        foreach ($locations as $location) {
            $map[$location->ID] = $service_map;
        }
        return $map;
    }

    function get_location_staff_map($map, $has_all_staff = false) {
        $map = array();
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_location'
                        ),
                        array()
        );
        $locations = $query->query();
        foreach ($locations as $location) {
            $map[$location->ID] = $location->get_assigned_staff();
        }
        $query = new BIRS_Model_Query(
                array(
                    'post_type' => 'birs_staff'
                ),
                array(
                    'base_keys' => array(
                        'post_title'
                    )
                )
        );
        $staff = $query->query();
        $staff_map = array();
        foreach($staff as $staff_id => $thestaff) {
            $staff_map[$staff_id] = $thestaff->post_title;
        }
        $map[-1] = $staff_map;
        if($has_all_staff) {
            foreach ($map as $location_id => $staff) {
                if (sizeof($staff) > 0) {
                    $map[$location_id][-1] = __('[All]', 'birchschedule');
                }
            }
        }
        return $map;
    }

    function get_service_staff_map() {
        $map = array();
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_service'
                        ),
                        array(
                            'meta_keys' => array(
                                '_birs_assigned_staff'
                            )
                        )
        );
        $services = $query->query();
        foreach ($services as $service) {
            $assigned_staff_ids = $service->get_assigned_staff_ids();
            $query = new BIRS_Model_Query(
                            array(
                                'post_type' => 'birs_staff'
                            ),
                            array(
                                'base_keys' => array(
                                    'post_title'
                                )
                            )
            );
            $staff = $query->query();
            $assigned_staff = array();
            foreach ($staff as $thestaff) {
                if (array_key_exists($thestaff->ID, $assigned_staff_ids)) {
                    $assigned_staff[$thestaff->ID] = $thestaff->post_title;
                }
                $map[$service->ID] = $assigned_staff;
            }
        }
        return $map;
    }

    function save_appointment() {
        if (isset($_POST['birs_appointment_id'])) {
            $appointment_id = $_POST['birs_appointment_id'];
        } else {
            $appointment_id = 0;
        }
        if (isset($_POST['birs_appointment_fields'])) {
            $fields = $_POST['birs_appointment_fields'];
        } else {
            $fields = array();
        }
        $fields = array_merge($fields, array(
            '_birs_appointment_service', '_birs_appointment_staff',
            '_birs_appointment_location', '_birs_appointment_price',
            '_birs_appointment_timestamp', '_birs_appointment_duration',
            '_birs_appointment_padding_before', '_birs_appointment_padding_after',
            '_birs_appointment_client', '_birs_appointment_payment_status',
            '_birs_appointment_uid'
                ));
        $appointment = new BIRS_Appointment($appointment_id, array(
                    'meta_keys' => $fields,
                    'base_keys' => array(
                        'post_title'
                    )
                ));
        $appointment->copyFromRequest($_POST);
        $client_id = $this->save_client();
        $appointment['_birs_appointment_client'] = $client_id;
        $appointment_id = $appointment->save();
        $payments = array();
        if(isset($_POST['birs_appointment_payments'])) {
            $payments = $_POST['birs_appointment_payments'];
        }
        do_action('birchschedule_save_appointment_payments', 
            $appointment_id, $client_id, $payments);
        return $appointment_id;
    }

    function save_client() {
        if (isset($_POST['birs_client_fields'])) {
            $fields = $_POST['birs_client_fields'];
        } else {
            $fields = array();
        }
        $client = new BIRS_Client(0, array(
                    'meta_keys' => $fields,
                    'base_keys' => array(
                        'post_title'
                    )
                ));
        $client->copyFromRequest($_POST);
        $client->load_id_by_email();
        $client_id = $client->save();

        return $client_id;
    }
    
    function validate_appointment_info() {
        $errors = array();
        $staff_text = 'service provider';
        if ($_POST['action'] == 'birs_save_appointment') {
            $staff_text = 'staff';
        }
        if (!isset($_POST['birs_appointment_staff']) || !isset($_POST['birs_appointment_service'])) {
            if ($_POST['action'] == 'birs_save_appointment') {
                $errors['birs_appointment_service'] = __('Please select a service and a staff', 'birchschedule');
            } else {
                $errors['birs_appointment_service'] = __('Please select a service and a service provider', 'birchschedule');
            }
        }
        if (!isset($_POST['birs_appointment_date']) || !$_POST['birs_appointment_date']) {
            $errors['birs_appointment_date'] = __('Date is required', 'birchschedule');
        }
        if (!isset($_POST['birs_appointment_time']) || !$_POST['birs_appointment_time']) {
            $errors['birs_appointment_time'] = __('Time is required', 'birchschedule');
        }
        if (isset($_POST['birs_appointment_date']) && $_POST['birs_appointment_date'] &&
            isset($_POST['birs_appointment_time']) && $_POST['birs_appointment_time']) {
            $datetime = array(
                'date' => $_POST['birs_appointment_date'],
                'time' => $_POST['birs_appointment_time']
            );
            $datetime = $this->get_util()->get_wp_datetime($datetime);
            if (!$datetime) {
                $errors['birs_appointment_datetime'] = __('Date & time is incorrect', 'birchschedule');
            } else {
                $timestamp = $datetime->format('U');
                $_POST['birs_appointment_timestamp'] = $timestamp;
            }
        }
        return $errors;
    }

    function validate_client_info() {
        $errors = array();
        if (!$_POST['birs_client_name_first']) {
            $errors['birs_client_name_first'] = __('First name is required', 'birchschedule');
        }
        if (!$_POST['birs_client_name_last']) {
            $errors['birs_client_name_last'] = __('Last name is required', 'birchschedule');
        }
        if (!$_POST['birs_client_email']) {
            $errors['birs_client_email'] = __('Email is required', 'birchschedule');
        } else if (!is_email($_POST['birs_client_email'])) {
            $errors['birs_client_email'] = __('Email is incorrect', 'birchschedule');
        }
        if (!$_POST['birs_client_phone']) {
            $errors['birs_client_phone'] = __('Phone is required', 'birchschedule');
        }

        return $errors;
    }

    function validate_booking_time() {
        $errors = array();
        if (!isset($_POST['birs_appointment_time']) || !$_POST['birs_appointment_time']) {
            $errors['birs_appointment_time'] = __('Time is required', 'birchschedule');
            return $errors;
        }
        $avaliable_times = $this->get_avaliable_time();
        $time = $_POST['birs_appointment_time'];
        $valid = array_key_exists($time, $avaliable_times) && $avaliable_times[$time]['avaliable'];
        if (!$valid) {
            $errors = array_merge(
                    array(
                'birs_appointment_time' => __('Time is unavaliable', 'birchschedule'
                    )), $errors);
        }
        return $errors;
    }

    function validate_booking_info_admin() {
        $errors = $this->validate_appointment_info();
        $client_errors = $this->validate_client_info();
        return array_merge($errors, $client_errors);
    }

    function validate_booking_form() {
        $errors = $this->validate_appointment_info();
        $client_errors = $this->validate_client_info();
        $time_error = $this->validate_booking_time();
        $errors = array_merge($errors, $time_error, $client_errors);
        return $errors;
    }

    function get_avaliable_time() {
        $location_id = 0;
        if (isset($_POST['birs_appointment_location'])) {
            $location_id = $_POST['birs_appointment_location'];
        }
        $service_id = 0;
        if (isset($_POST['birs_appointment_service'])) {
            $service_id = $_POST['birs_appointment_service'];
        }
        $staff_id = 0;
        if (isset($_POST['birs_appointment_staff'])) {
            $staff_id = $_POST['birs_appointment_staff'];
        }
        $date = 0;
        if (isset($_POST['birs_appointment_date'])) {
            $date = $_POST['birs_appointment_date'];
        }
        if (!($location_id && $service_id && $staff_id && $date)) {
            return array();
        }
        $staff = new BIRS_Staff($staff_id, array(
                    'meta_keys' => array(
                        '_birs_staff_schedule'
                    )
                ));
        $staff->load();
        $time_options = $staff->get_avaliable_time($location_id, $service_id, $date);
        return $time_options;
    }

    function get_location_listing_order($orders, $has_all = true) {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_location',
                            'order' => 'ASC',
                            'orderby' => 'title'
                        ),
                        array(
                            'base_keys' => array(
                                'post_title'
                            )
                        )
        );
        $locations = $query->query();
        $locations_order = array_keys($locations);
        if($has_all) {
            array_unshift($locations_order, -1);
        }
        return $locations_order;
    }

    function get_staff_listing_order($orders, $has_all = true) {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_staff',
                            'order' => 'ASC',
                            'orderby' => 'title'
                        ),
                        array(
                            'base_keys' => array(
                                'post_title'
                            )
                        )
        );
        $staff = $query->query();
        $staff_order = array_keys($staff);
        if($has_all) {
            array_unshift($staff_order, -1);
        }
        return $staff_order;
    }

    function get_service_listing_order() {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_service',
                            'order' => 'ASC',
                            'orderby' => 'title'
                        ),
                        array(
                            'base_keys' => array(
                                'post_title'
                            )
                        )
        );
        $services = $query->query();
        return array_keys($services);
    }
    
    function get_service_length($service_id) {
        $length = get_post_meta($service_id, '_birs_service_length', true);
        $length_type = get_post_meta($service_id, '_birs_service_length_type', true);
        if ($length_type == 'hours') {
            $length = $length * 60;
        }
        $padding = get_post_meta($service_id, '_birs_service_padding', true);
        $padding_type = get_post_meta($service_id, '_birs_service_padding_type', true);
        if ($padding_type == 'before-and-after') {
            $padding *= 2;
        }
        return $length + $padding;
    }

    function get_util() {
        return BIRS_Util::get_instance();
    }

}