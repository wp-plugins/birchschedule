<?php

if(!class_exists('Birchschedule_View_Services')) {

    class Birchschedule_View_Services extends Birchpress_Lang_Package {
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

            $this->define_function('render_service_info');

            $this->define_function('render_assign_staff');

            $this->define_function('get_length_types');

            $this->define_function('get_padding_types');

            $this->define_function('get_price_types');

            $birchschedule->view->define_method('load_page_edit', 
                'birs_service', array('Birchschedule_View_Services_Imp', 'load_page_edit_birs_service'));

            $birchschedule->view->define_method('enqueue_scripts_edit',
                'birs_service', array('Birchschedule_View_Services_Imp', 'enqueue_scripts_edit_birs_service'));

            $birchschedule->view->define_method('save_post',
                'birs_service', array('Birchschedule_View_Services_Imp', 'save_service'));

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Services();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->services = Birchschedule_View_Services::get_instance();

    require_once 'imp.php';
}
