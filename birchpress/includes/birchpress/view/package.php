<?php

if(!class_exists('Birchpress_View')) {

	class Birchpress_View extends Birchpress_Lang_Package {
		private static $instance;

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchpress_View();
			}
			return self::$instance;
		}

        private function __construct() {
        }

        public function define_interface() {

			$this->define_multimethod('load_post_edit', 'post_type');

			$this->define_multimethod('load_post_new', 'post_type');

			$this->define_multimethod('enqueue_scripts_post_new', 'post_type');

			$this->define_multimethod('enqueue_scripts_post_edit', 'post_type');

			$this->define_multimethod('enqueue_scripts_post_list', 'post_type');

			$this->define_multimethod('save_post', 'post_type');

			$this->define_multimethod('pre_save_post', 'post_type');

		}
	}

	$GLOBALS['birchpress']->view = Birchpress_View::get_instance();

	require_once "imp.php";

}

