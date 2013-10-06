<?php

if(!class_exists('Birchschedule_View_Bookingform')) {

    class Birchschedule_View_Bookingform extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {
            global $birchschedule;

            $this->define_function('init');

            $this->define_function('wp_init');

            $this->define_function('ajax_save_appointment');

            $this->define_function('ajax_get_avaliable_time');

            $this->define_function('enqueue_scripts');

            $this->define_function('get_booking_response');

            $this->define_function('get_all_schedule');

            $this->define_function('get_all_daysoff');

            $this->define_function('get_locations_map');

            $this->define_function('get_locations_staff_map');

            $this->define_function('get_locations_services_map');

            $this->define_function('get_services_staff_map');

            $this->define_function('get_locations_listing_order');

            $this->define_function('get_staff_listing_order');

            $this->define_function('get_services_listing_order');

            $this->define_function('get_services_prices_map');

            $this->define_function('get_services_duration_map');

            $this->define_function('validate_booking_info');

            $this->define_function('validate_appointment_info');

            $this->define_function('validate_client_info');

            $this->define_function('validate_booking_time');

            $this->define_function('extract_appointment');

            $this->define_function('extract_appointment_meta_keys');

            $this->define_function('save_appointment');

            $this->define_function('save_client');

            $this->define_function('get_avaliable_time');

            $this->define_function('get_shortcode_html');

            $this->define_function('get_fields_html');

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Bookingform();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->bookingform = Birchschedule_View_Bookingform::get_instance();

    require_once 'imp.php';
}
