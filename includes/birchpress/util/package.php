<?php

if(!class_exists('Birchpress_Util')) {

	class Birchpress_Util extends Birchpress_Lang_Package {
		private static $instance;

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchpress_Util();
			}
			return self::$instance;
		}

        private function __construct() {
        }

        public function define_interface() {
			$this->define_function('init');

			$this->define_function('date_time_format_php_to_jquery');

			$this->define_function('date_time_format_php_to_fullcalendar');

			$this->define_function('convert_to_datetime');

			$this->define_function('get_wp_timezone');

			$this->define_function('date_i18n');

			$this->define_function('get_day_minutes');

			$this->define_function('has_shortcode');

			$this->define_function('to_mysql_date');

			$this->define_function('get_wp_datetime');

			$this->define_function('get_weekdays_short');

			$this->define_function('get_calendar_views');

			$this->define_function('get_countries');

			$this->define_function('get_us_states');

			$this->define_function('get_currencies');

			$this->define_function('convert_mins_to_time_option');

			$this->define_function('get_time_options');

			$this->define_function('get_client_title_options');

			$this->define_function('get_gmt_offset');

			$this->define_function('render_html_options');

			$this->define_function('get_fullcalendar_i18n_params');

			$this->define_function('get_datepicker_i18n_params');

			$this->define_function('starts_with');

			$this->define_function('ends_with');

			$this->define_function('current_page_url');

			$this->define_function('debug_mode');
			
			$this->define_function('log');
		}
	}

	$GLOBALS['birchpress']->util = Birchpress_Util::get_instance();

	require_once 'imp.php';
}

