<?php

if(!class_exists('Birchschedule_Model')) {

	class Birchschedule_Model extends Birchpress_Lang_Package {
		private static $instance;

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchschedule_Model();
			}
			return self::$instance;
		}

		private function __construct() {	
		}		

		public function define_interface() {	
			global $birchpress;

			$this->define_function('get');

			$this->define_function('delete');

			$this->define_function('query');

			$this->define_function('is_valid_id', array($birchpress->db, 'is_valid_id'));

			$this->define_multimethod('save', 'post_type');

			$this->define_multimethod('pre_save', 'post_type');
			$this->define_method('pre_save', 'birs_appointment');
			$this->define_method('pre_save', 'birs_client');
			$this->define_method('pre_save', 'birs_location');
			$this->define_method('pre_save', 'birs_payment');
			$this->define_method('pre_save', 'birs_service');
			$this->define_method('pre_save', 'birs_staff');

			$this->define_multimethod('post_get', 'post_type');
			$this->define_method('post_get', 'birs_appointment');
			$this->define_method('post_get', 'birs_client');
			$this->define_method('post_get', 'birs_location');
			$this->define_method('post_get', 'birs_payment');
			$this->define_method('post_get', 'birs_service');
			$this->define_method('post_get', 'birs_staff');

			$this->define_function('get_appointment_title');

			$this->define_function('get_appointment_pre_payment_fee');

			$this->define_function('get_service_pre_payment_fee');

			$this->define_function('get_service_padding');

			$this->define_function('get_service_padding_before');

			$this->define_function('get_service_padding_after');

			$this->define_function('get_service_length');

			$this->define_function('get_service_length_with_paddings');

			$this->define_function('get_service_timeslot');

			$this->define_function('get_staff_schedule_by_location');

			$this->define_function('get_default_country');

			$this->define_function('get_first_day_of_week');

			$this->define_function('update_model_relations');

			$this->define_function('is_prepayment_enabled');

			//facade
			$this->define_function('get_appointment_meta_keys', 
				array('Birchschedule_Model_Facade_Imp', 'get_appointment_meta_keys'));

			$this->define_function('get_client_meta_keys', 
				array('Birchschedule_Model_Facade_Imp', 'get_client_meta_keys'));

			$this->define_function('get_meta_key_label',
				array('Birchschedule_Model_Facade_Imp', 'get_meta_key_label'));

			$this->define_function('get_client_by_email', 
				array('Birchschedule_Model_Facade_Imp', 'get_client_by_email'));

			$this->define_function('get_services_by_location', 
				array('Birchschedule_Model_Facade_Imp', 'get_services_by_location'));

			$this->define_function('get_staff_by_location', 
				array('Birchschedule_Model_Facade_Imp', 'get_staff_by_location'));

			$this->define_function('get_services_by_staff', 
				array('Birchschedule_Model_Facade_Imp', 'get_services_by_staff'));

			$this->define_function('get_staff_by_service', 
				array('Birchschedule_Model_Facade_Imp', 'get_staff_by_service'));

			$this->define_function('get_locations_map', 
				array('Birchschedule_Model_Facade_Imp', 'get_locations_map'));

			$this->define_function('get_locations_services_map', 
				array('Birchschedule_Model_Facade_Imp', 'get_locations_services_map'));

			$this->define_function('get_locations_staff_map', 
				array('Birchschedule_Model_Facade_Imp', 'get_locations_staff_map'));

			$this->define_function('get_services_staff_map',
				array('Birchschedule_Model_Facade_Imp', 'get_services_staff_map'));

			$this->define_function('get_locations_listing_order', 
				array('Birchschedule_Model_Facade_Imp', 'get_locations_listing_order'));

			$this->define_function('get_staff_listing_order', 
				array('Birchschedule_Model_Facade_Imp', 'get_staff_listing_order'));

			$this->define_function('get_services_listing_order', 
				array('Birchschedule_Model_Facade_Imp', 'get_services_listing_order'));

			$this->define_function('get_services_prices_map',
				array('Birchschedule_Model_Facade_Imp', 'get_services_prices_map'));

			$this->define_function('get_services_duration_map',
				array('Birchschedule_Model_Facade_Imp', 'get_services_duration_map'));

			$this->define_function('apply_currency_symbol', 
				array('Birchschedule_Model_Facade_Imp', 'apply_currency_symbol'));

			$this->define_function('get_currency_code',
				array('Birchschedule_Model_Facade_Imp', 'get_currency_code'));

			$this->define_function('query_appointments', 
				array('Birchschedule_Model_Facade_Imp', 'query_appointments'));
			
			$this->define_function('get_cut_off_time', 
				array('Birchschedule_Model_Facade_Imp', 'get_cut_off_time'));
			
			$this->define_function('get_future_time', 
				array('Birchschedule_Model_Facade_Imp', 'get_future_time'));
			
			$this->define_function('get_staff_daysoff', 
				array('Birchschedule_Model_Facade_Imp', 'get_staff_daysoff'));
			
			$this->define_function('get_all_daysoff', 
				array('Birchschedule_Model_Facade_Imp', 'get_all_daysoff'));
			
			//schedule
			$this->define_function('get_staff_busy_time', 
				array('Birchschedule_Model_Schedule_Imp', 'get_staff_busy_time'));

			$this->define_function('get_staff_calculated_schedule',
				array('Birchschedule_Model_Schedule_Imp', 'get_staff_calculated_schedule'));

			$this->define_function('get_staff_calculated_schedule_by_location',
				array('Birchschedule_Model_Schedule_Imp', 'get_staff_calculated_schedule_by_location'));

			$this->define_function('get_all_calculated_schedule',
				array('Birchschedule_Model_Schedule_Imp', 'get_all_calculated_schedule'));

			$this->define_function('get_staff_schedule_date_start', 
				array('Birchschedule_Model_Schedule_Imp', 'get_staff_schedule_date_start'));

			$this->define_function('get_staff_schedule_date_end', 
				array('Birchschedule_Model_Schedule_Imp', 'get_staff_schedule_date_end'));

			$this->define_function('get_staff_exception_date_start', 
				array('Birchschedule_Model_Schedule_Imp', 'get_staff_exception_date_start'));

			$this->define_function('get_staff_exception_date_end', 
				array('Birchschedule_Model_Schedule_Imp', 'get_staff_exception_date_end'));

			$this->define_function('get_staff_avaliable_time', 
				array('Birchschedule_Model_Schedule_Imp', 'get_staff_avaliable_time'));

			//merge fields
			$this->define_function('get_full_appointment', 
				array('Birchschedule_Model_Mergefields_Imp', 'get_full_appointment'));

			$this->define_function('get_merge_fields_map', 
				array('Birchschedule_Model_Mergefields_Imp', 'get_merge_fields_map'));

			$this->define_function('get_merge_field_display_value', 
				array('Birchschedule_Model_Mergefields_Imp', 'get_merge_field_display_value'));

			$this->define_function('apply_merge_fields', 
				array('Birchschedule_Model_Mergefields_Imp', 'apply_merge_fields'));

		}
	}

	$GLOBALS['birchschedule']->model = Birchschedule_Model::get_instance();

	require_once 'imp.php';
	require_once 'facade_imp.php';
	require_once 'mergefields_imp.php';
	require_once 'schedule_imp.php';

}

