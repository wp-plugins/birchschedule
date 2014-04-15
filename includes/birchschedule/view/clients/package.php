<?php

if(!class_exists('Birchschedule_View_Clients')) {

    class Birchschedule_View_Clients extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {
            global $birchschedule;

            $birchschedule->view->define_method('load_page_edit', 
                'birs_client', array('Birchschedule_View_Clients_Imp', 'load_page_edit_birs_client'));

            $birchschedule->view->define_method('enqueue_scripts_edit',
                'birs_client', array('Birchschedule_View_Clients_Imp', 'enqueue_scripts_edit_birs_client'));

            $birchschedule->view->define_method('enqueue_scripts_list',
                'birs_client', array('Birchschedule_View_Clients_Imp', 'enqueue_scripts_list_birs_client'));

            $birchschedule->view->define_method('save_post',
                'birs_client', array('Birchschedule_View_Clients_Imp', 'save_client'));

            $birchschedule->view->define_method('pre_save_post',
                'birs_client', array('Birchschedule_View_Clients_Imp', 'pre_save_client'));

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Clients();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->clients = Birchschedule_View_Clients::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view->clients);

    }
