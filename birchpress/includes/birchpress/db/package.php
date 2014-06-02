<?php

if(!class_exists('Birchpress_Db')) {

	class Birchpress_Db extends Birchpress_Lang_Package {
		private static $instance;

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchpress_Db();
			}
			return self::$instance;
		}

        private function __construct() {
        }

        public function define_interface() {

			$this->define_multimethod('save', 'post_type');
			
		}
	}

	$GLOBALS['birchpress']->db = Birchpress_Db::get_instance();

	require_once "imp.php";
}

