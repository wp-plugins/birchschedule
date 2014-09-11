<?php

if(!class_exists('Birchschedule_View_Settings')) {

    class Birchschedule_View_Settings extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {

            $this->define_multimethod('init_tab', 'tab');

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Settings();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->settings = Birchschedule_View_Settings::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view->settings);

    }
