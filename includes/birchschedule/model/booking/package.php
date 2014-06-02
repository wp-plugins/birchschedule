<?php

if(!class_exists('Birchschedule_Model_Booking')) {

	class Birchschedule_Model_Booking extends Birchpress_Lang_Package {
		private static $instance;

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchschedule_Model_Booking();
			}
			return self::$instance;
		}

		private function __construct() {	
		}		

		public function define_interface() {
		}
	}

	$GLOBALS['birchschedule']->model->booking = Birchschedule_Model_Booking::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->model->booking);

	
}

