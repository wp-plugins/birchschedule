<?php

if(!class_exists('Birchschedule_View_Help')) {

    class Birchschedule_View_Help extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {

            $this->define_function('init');

            $this->define_function('wp_admin_init');

            $this->define_function('load_page');

            $this->define_function('render_admin_page');
            
        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Help();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->help = Birchschedule_View_Help::get_instance();

    require_once 'imp.php';
}
