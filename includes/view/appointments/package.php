<?php

if(!class_exists('Birchschedule_View_Appointments')) {

    class Birchschedule_View_Appointments extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }        

        public function define_interface() {

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Appointments();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->appointments = Birchschedule_View_Appointments::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view->appointments);

}
