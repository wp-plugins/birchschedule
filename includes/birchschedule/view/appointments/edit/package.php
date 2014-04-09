<?php

if(!class_exists('Birchschedule_View_Appointments_Edit')) {

    class Birchschedule_View_Appointments_Edit extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }        

        public function define_interface() {
            global $birchschedule;

            $birchschedule->view->define_method('load_post_edit', 
                'birs_appointment', array('Birchschedule_View_Appointments_Edit_Imp', 'load_post_edit_birs_appointment'));

            $birchschedule->view->define_method('enqueue_scripts_post_edit',
                'birs_appointment', array('Birchschedule_View_Appointments_Edit_Imp', 'enqueue_scripts_post_edit_birs_appointment'));
        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Appointments_Edit();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->appointments->edit = Birchschedule_View_Appointments_Edit::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view->appointments->edit);

    }
