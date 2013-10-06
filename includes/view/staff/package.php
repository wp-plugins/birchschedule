<?php

if(!class_exists('Birchschedule_View_Staff')) {

    class Birchschedule_View_Staff extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {
            global $birchschedule;

            $this->define_function('init');

            $this->define_function('wp_admin_init');

            $this->define_function('wp_init');

            $this->define_function('get_edit_columns');

            $this->define_function('get_updated_messages');

            $this->define_function('add_meta_boxes');

            $this->define_function('ajax_new_schedule');

            $this->define_function('get_schedule_interval');

            $this->define_function('render_schedule');

            $this->define_function('render_timetable');

            $this->define_function('render_work_schedule');

            $this->define_function('render_assign_services');

            $birchschedule->view->define_method('load_page_edit', 
                'birs_staff', array('Birchschedule_View_Staff_Imp', 'load_page_edit_birs_staff'));

            $birchschedule->view->define_method('enqueue_scripts_edit',
                'birs_staff', array('Birchschedule_View_Staff_Imp', 'enqueue_scripts_edit_birs_staff'));

            $birchschedule->view->define_method('save_post',
                'birs_staff', array('Birchschedule_View_Staff_Imp', 'save_staff'));

        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Staff();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->staff = Birchschedule_View_Staff::get_instance();

    require_once 'imp.php';

}
