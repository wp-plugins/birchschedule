<?php

if(!class_exists('Birchschedule_Gsettings')) {

    final class Birchschedule_Gsettings extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {}

        public function define_interface() {
            global $birchschedule;

            $this->define_function('init');

            $this->define_function('add_settings_sections');

            $this->define_function('add_settings_fields');

            $this->define_function('render_page');

            $this->define_function('sanitize_input');

            $this->define_function('upgrade_module',
                array('Birchschedule_Gsettings_Upgrader', 'upgrade_module'));

            $birchschedule->define_method('upgrade_module', 'gsettings',
                array($this, 'upgrade_module'));

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_Gsettings();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->gsettings = Birchschedule_Gsettings::get_instance();

    require_once 'imp.php';
    require_once 'upgrader.php';

}
