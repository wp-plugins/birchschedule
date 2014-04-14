<?php

if(!class_exists('Birchschedule_View_Locations')) {

    class Birchschedule_View_Locations extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {
            global $birchschedule;

            $birchschedule->view->define_method('load_page_edit', 
                'birs_location', array('Birchschedule_View_Locations_Imp', 'load_page_edit_birs_location'));

            $birchschedule->view->define_method('enqueue_scripts_edit',
                'birs_location', array('Birchschedule_View_Locations_Imp', 'enqueue_scripts_edit_birs_location'));

            $birchschedule->view->define_method('save_post',
                'birs_location', array('Birchschedule_View_Locations_Imp', 'save_location'));

            $birchschedule->view->define_method('pre_save_post',
                'birs_location', array('Birchschedule_View_Locations_Imp', 'pre_save_location'));
        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Locations();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->locations = Birchschedule_View_Locations::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view->locations);

    }
