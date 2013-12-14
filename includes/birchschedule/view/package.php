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
			
			$this->define_function('init');

			$this->define_function('wp_init');

			$this->define_function('wp_loaded');

			$this->define_function('wp_admin_init');

			$this->define_function('create_menu_scheduler');

			$this->define_function('reorder_submenus');

			$this->define_function('get_submenu');

			$this->define_function('register_script_data');

			$this->define_function('get_admin_i18n_messages');

			$this->define_function('get_frontend_i18n_messages');

			$this->define_function('render_ajax_success_message');

			$this->define_function('render_ajax_error_messages');
			
			$this->define_function('register_common_scripts_data');

			$this->define_function('localize_scripts');

			$this->define_function('register_common_scripts');

			$this->define_function('register_common_styles');

			$this->define_function('enqueue_scripts');

			$this->define_function('enqueue_styles');

			$this->define_function('merge_request');

			$this->define_function('get_current_post_type');

			$this->define_multimethod('enqueue_scripts_edit', 'post_type');

			$this->define_multimethod('load_page_edit', 'post_type');

			$this->define_multimethod('save_post', 'post_type');

			$this->define_multimethod('pre_save_post', 'post_type');

			$this->define_function('apply_currency_to_label');

			$this->define_function('render_errors');

			$this->define_function('get_errors');

			$this->define_function('has_errors');

			$this->define_function('save_errors');

			$this->define_function('get_screen');

			$this->define_function('show_notice');

			$this->define_function('add_page_hook');

			$this->define_function('get_page_hook');

			$this->define_function('get_custom_code_css');

			$this->define_function('get_shortcodes');

			$this->define_function('load_i18n');

			$this->define_function('create_admin_menus');

			$this->define_function('render_calendar_page', 
				array($birchschedule->view->calendar, 'render_admin_page'));

			$this->define_function('render_settings_page', 
				array($birchschedule->view->settings, 'render_admin_page'));

			$this->define_function('render_help_page', 
				array($birchschedule->view->help, 'render_admin_page'));
		}
	}

	$GLOBALS['birchschedule']->view = Birchschedule_View::get_instance();

	require_once 'imp.php';
}

