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

		}
	}

	$GLOBALS['birchschedule']->model = Birchschedule_Model::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->model);

	
}

