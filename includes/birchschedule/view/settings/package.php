<?php

if(!class_exists('Birchschedule_View_Settings')) {

    class Birchschedule_View_Settings extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {

            $this->define_function('init');

            $this->define_function('wp_admin_init');

            $this->define_function('enqueue_scripts');

            $this->define_function('get_tabs');

            $this->define_multimethod('init_tab', 'tab');

            $this->define_function('get_tab_metabox_category');

            $this->define_function('get_tab_save_action_name');

            $this->define_function('get_tab_page_hook');

            $this->define_function('get_tab_options_name');

            $this->define_function('get_tab_transient_message_name');

            $this->define_function('save_tab_options');

            $this->define_function('render_tab_page');

            $this->define_function('render_admin_page');

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Settings();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->settings = Birchschedule_View_Settings::get_instance();

    require_once 'imp.php';
}
