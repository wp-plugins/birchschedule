<?php

if(!class_exists('Birchschedule_Model_Mergefields')) {

	class Birchschedule_Model_Mergefields extends Birchpress_Lang_Package {
		private static $instance;

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchschedule_Model_Mergefields();
			}
			return self::$instance;
		}

		private function __construct() {	
		}		

		public function define_interface() {
		}
	}

	$GLOBALS['birchschedule']->model->mergefields = Birchschedule_Model_Mergefields::get_instance();

    $GLOBALS['birchschedule']->add_core_package($GLOBALS['birchschedule']->model->mergefields);

	
}

