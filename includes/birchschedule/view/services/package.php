<?php

if(!class_exists('Birchschedule_View_Services')) {

    class Birchschedule_View_Services extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {
            global $birchschedule;

            $birchschedule->view->define_method('load_page_edit', 
                'birs_service', array('Birchschedule_View_Services_Imp', 'load_page_edit_birs_service'));

            $birchschedule->view->define_method('enqueue_scripts_edit',
                'birs_service', array('Birchschedule_View_Services_Imp', 'enqueue_scripts_edit_birs_service'));

            $birchschedule->view->define_method('save_post',
                'birs_service', array('Birchschedule_View_Services_Imp', 'save_service'));

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Services();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->services = Birchschedule_View_Services::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view->services);

    }
