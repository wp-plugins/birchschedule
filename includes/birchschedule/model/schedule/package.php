<?php

if(!class_exists('Birchschedule_Model_Schedule')) {

	class Birchschedule_Model_Schedule extends Birchpress_Lang_Package {
		private static $instance;

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchschedule_Model_Schedule();
			}
			return self::$instance;
		}

		private function __construct() {	
		}		

		public function define_interface() {

		}
	}

	$GLOBALS['birchschedule']->model->schedule = Birchschedule_Model_Schedule::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->model->schedule);

	
}

