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
		}
	}

	$GLOBALS['birchpress']->util = Birchpress_Util::get_instance();

	require_once "imp.php";

}

