<?php

if(!class_exists('Birchschedule_Eadmin')) {

    final class Birchschedule_Eadmin extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {}

        public function define_interface() {
            global $birchschedule;

            $this->define_function('init');

            $this->define_function('enqueue_scripts');

            $this->define_function('ajax_load_selected_client');

            $this->define_function('get_client_general_html');

            $this->define_function('get_appointment_duration_html');

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_Eadmin();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->eadmin = Birchschedule_Eadmin::get_instance();

    require_once 'imp.php';

}
