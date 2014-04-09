<?php

if(!class_exists('Birchschedule_View_Appointments_New_New')) {

    class Birchschedule_View_Appointments_New extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }        

        public function define_interface() {
            global $birchschedule;

            $birchschedule->view->define_method('load_post_new', 
                'birs_appointment', array('Birchschedule_View_Appointments_New_Imp', 'load_post_new_birs_appointment'));

            $birchschedule->view->define_method('enqueue_scripts_post_new',
                'birs_appointment', array('Birchschedule_View_Appointments_New_Imp', 'enqueue_scripts_post_new_birs_appointment'));

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Appointments_New();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->appointments->new = Birchschedule_View_Appointments_New::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view->appointments->new);
}
