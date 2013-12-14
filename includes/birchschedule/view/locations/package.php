<?php

if(!class_exists('Birchschedule_View_Locations')) {

    class Birchschedule_View_Locations extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {
            global $birchschedule;

            $this->define_function('init');

            $this->define_function('wp_admin_init');

            $this->define_function('wp_init');

            $this->define_function('get_edit_columns');

            $this->define_function('render_custom_columns');

            $this->define_function('get_updated_messages');

            $this->define_function('add_meta_boxes');

            $this->define_function('render_location_info');

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

    require_once 'imp.php';
}
