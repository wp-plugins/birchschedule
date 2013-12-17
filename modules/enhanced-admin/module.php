<?php

if(!class_exists('Birchschedule_Module_Eadmin')) {

	require_once 'package.php';

	final class Birchschedule_Module_Eadmin extends Birchpress_Module {
		private static $instance;

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchschedule_Module_Eadmin();
			}
			return self::$instance;
		}

		function __construct() {
			parent::__construct();

			global $birchschedule;

			$this->add_package($birchschedule->eadmin);
		}

	}

	$GLOBALS['birchschedule']->add_module('eadmin', Birchschedule_Module_Eadmin::get_instance());

}
