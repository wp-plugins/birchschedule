<?php

if(!class_exists('Birchschedule_View_Staff')) {

    class Birchschedule_View_Staff extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {
            global $birchschedule;

            $birchschedule->view->define_method('load_page_edit', 
                'birs_staff', array('Birchschedule_View_Staff_Imp', 'load_page_edit_birs_staff'));

            $birchschedule->view->define_method('enqueue_scripts_edit',
                'birs_staff', array('Birchschedule_View_Staff_Imp', 'enqueue_scripts_edit_birs_staff'));

            $birchschedule->view->define_method('save_post',
                'birs_staff', array('Birchschedule_View_Staff_Imp', 'save_staff'));

            $birchschedule->view->define_method('pre_save_post',
                'birs_staff', array('Birchschedule_View_Staff_Imp', 'pre_save_staff'));
        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Staff();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->staff = Birchschedule_View_Staff::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view->staff);

    
}
