<?php

if(!class_exists('Birchschedule_View_Bookingform')) {

    class Birchschedule_View_Bookingform extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {
        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Bookingform();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->bookingform = Birchschedule_View_Bookingform::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view->bookingform);

}
