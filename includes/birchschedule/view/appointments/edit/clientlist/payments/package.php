<?php

if(!class_exists('Birchschedule_View_Appointments_Edit_Clientlist_Payments')) {

    class Birchschedule_View_Appointments_Edit_Clientlist_Payments extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {
            
        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Appointments_Edit_Clientlist_Payments();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->appointments->edit->clientlist->payments = 
        Birchschedule_View_Appointments_Edit_Clientlist_Payments::get_instance();

    $GLOBALS['birchschedule']->add_core_package(
        $GLOBALS['birchschedule']->view->appointments->edit->clientlist->payments);

}
