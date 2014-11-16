<?php

if(!class_exists('Birchschedule_View_Help')) {

    class Birchschedule_View_Help extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {
          
        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Help();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->help = Birchschedule_View_Help::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view->help);

    }
