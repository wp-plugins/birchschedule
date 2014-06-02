<?php

if(!class_exists('Birchschedule_View_Calendar')) {

    class Birchschedule_View_Calendar extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }        

        public function define_interface() {

            $this->page_hooks = array();

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Calendar();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->calendar = Birchschedule_View_Calendar::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view->calendar);

    }
