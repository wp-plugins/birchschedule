<?php

if(!class_exists('Birchschedule_View_Appointments_Edit_Clientlist_Cancel')) {

    class Birchschedule_View_Appointments_Edit_Clientlist_Cancel extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }        

        public function define_interface() {

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Appointments_Edit_Clientlist_Cancel();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->appointments->edit->clientlist->cancel = 
        Birchschedule_View_Appointments_Edit_Clientlist_Cancel::get_instance();

    $GLOBALS['birchschedule']->add_core_package(
        $GLOBALS['birchschedule']->view->appointments->edit->clientlist->cancel);

    }
