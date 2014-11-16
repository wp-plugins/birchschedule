<?php

if(!class_exists('Birchpress')) {

	final class Birchpress {
		private static $instance;

        private $version;

        private $framework_url;

        private $framework_file_path;

		private function __construct() {
		}

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchpress();
			}
			return self::$instance;
		}

        function get_version() {
            return $this->version;
        }

        function set_version($version) {
            $this->version = $version;
        }

        function set_plugin_url($plugin_url) {
            $this->framework_url = $plugin_url . '/framework';
        }

        function get_framework_url() {
            return $this->framework_url;
        }
	}

	$GLOBALS['birchpress'] = Birchpress::get_instance();

}