<?php

if(!class_exists('Birchschedule_View')) {

	class Birchschedule_View extends Birchpress_Lang_Package {
		private static $instance;

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchschedule_View();
			}
			return self::$instance;
		}

		private function __construct() {
		}

		public function define_interface() {
			global $birchschedule;

			$this->define_function('render_calendar_page', 
				array($birchschedule->view->calendar, 'render_admin_page'));

			$this->define_function('render_settings_page', 
				array($birchschedule->view->settings, 'render_admin_page'));

			$this->define_function('render_help_page', 
				array($birchschedule->view->help, 'render_admin_page'));

			$this->define_multimethod('enqueue_scripts_post_edit', 'post_type');

			$this->define_multimethod('enqueue_scripts_post_new', 'post_type');

			$this->define_multimethod('enqueue_scripts_edit', 'post_type');

			$this->define_multimethod('enqueue_scripts_list', 'post_type');

			$this->define_multimethod('load_page_edit', 'post_type');

			$this->define_multimethod('load_post_edit', 'post_type');

			$this->define_multimethod('load_post_new', 'post_type');

			$this->define_multimethod('save_post', 'post_type');

			$this->define_multimethod('pre_save_post', 'post_type');

		}
	}

	$GLOBALS['birchschedule']->view = Birchschedule_View::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->view);
}

