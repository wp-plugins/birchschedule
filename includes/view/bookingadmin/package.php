<?php

if(!class_exists('Birchschedule_View_Bookingadmin')) {

    class Birchschedule_View_Bookingadmin extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }        

        public function define_interface() {

            $this->page_hooks = array();

            $this->define_function('init');

            $this->define_function('wp_admin_init');
            
            $this->define_function('ajax_render_edit_form');

            $this->define_function('ajax_save_appointment');

            $this->define_function('ajax_delete_appointment');

            $this->define_function('enqueue_scripts');

            $this->define_function('get_scripts');

            $this->define_function('get_styles');

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

            $this->define_function('extract_appointment');

            $this->define_function('extract_appointment_meta_keys');

            $this->define_function('save_appointment');

            $this->define_function('save_client');

            $this->define_function('get_client_details_html');

            $this->define_function('get_appointment_details_html');

            $this->define_function('get_appointment_duration_html');

            $this->define_function('get_appointment_general_html');

            $this->define_function('get_client_general_html');

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Bookingadmin();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->bookingadmin = Birchschedule_View_Bookingadmin::get_instance();

    require_once 'imp.php';
}
