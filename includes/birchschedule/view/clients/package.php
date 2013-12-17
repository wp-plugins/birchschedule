<?php

if(!class_exists('Birchschedule_View_Clients')) {

    class Birchschedule_View_Clients extends Birchpress_Lang_Package {
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

            $this->define_function('get_client_details_html');

            $this->define_function('render_client_info');

            $birchschedule->view->define_method('load_page_edit', 
                'birs_client', array('Birchschedule_View_Clients_Imp', 'load_page_edit_birs_client'));

            $birchschedule->view->define_method('enqueue_scripts_edit',
                'birs_client', array('Birchschedule_View_Clients_Imp', 'enqueue_scripts_edit_birs_client'));

            $birchschedule->view->define_method('save_post',
                'birs_client', array('Birchschedule_View_Clients_Imp', 'save_client'));

            $birchschedule->view->define_method('pre_save_post',
                'birs_client', array('Birchschedule_View_Clients_Imp', 'pre_save_client'));

            $this->define_function('validate_data');

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Clients();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->clients = Birchschedule_View_Clients::get_instance();

    require_once 'imp.php';
}
