<?php

if(!class_exists('Birchschedule_View_Appointments_Edit_Clientlist')) {

    class Birchschedule_View_Appointments_Edit_Clientlist extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }        

        public function define_interface() {

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Appointments_Edit_Clientlist();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->appointments->edit->clientlist = 
        Birchschedule_View_Appointments_Edit_Clientlist::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view->appointments->edit->clientlist);

    }
