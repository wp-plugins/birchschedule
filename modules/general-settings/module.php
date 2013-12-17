<?php

if(!class_exists('Birchschedule_Module_Gsettings')) {

	require_once 'package.php';

	final class Birchschedule_Module_Gsettings extends Birchpress_Module {
		private static $instance;

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchschedule_Module_Gsettings();
			}
			return self::$instance;
		}

		function __construct() {
			parent::__construct();

			global $birchschedule;

			$this->add_package($birchschedule->gsettings);
		}

	}

	$GLOBALS['birchschedule']->add_module('gsettings', Birchschedule_Module_Gsettings::get_instance());

}
