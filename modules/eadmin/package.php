<?php

if(!class_exists('Birchschedule_Eadmin')) {

    final class Birchschedule_Eadmin extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {}

        public function define_interface() {
        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_Eadmin();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->eadmin = Birchschedule_Eadmin::get_instance();

    
}
