<?php

if(!class_exists('Birchschedule_View_Calendar')) {

    class Birchschedule_View_Calendar extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }        

        public function define_interface() {

            $this->page_hooks = array();

            $this->define_function('init');

            $this->define_function('wp_admin_init');
            
            $this->define_function('render_admin_page');

            $this->define_function('ajax_query_appointments');

            $this->define_function('enqueue_scripts');

            $this->define_function('get_default_view');

            $this->define_function('query_appointments');

            $this->define_function('get_locations_map');

            $this->define_function('get_locations_staff_map');

            $this->define_function('get_locations_listing_order');

            $this->define_function('get_staff_listing_order');

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Calendar();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->calendar = Birchschedule_View_Calendar::get_instance();

    require_once 'imp.php';
}
