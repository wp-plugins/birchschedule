<?php

if(!class_exists('Birchschedule')) {

	final class Birchschedule extends Birchpress_Lang_Plugin {
		private static $instance;

		protected function __construct() {
			parent::__construct('birchschedule');
		}

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchschedule();
			}
			return self::$instance;
		}

		public function define_interface() {
			parent::define_interface();
            $this->define_function('upgrade_core', 
                array('Birchschedule_Upgrader', 'upgrade_core'));

		}

        public function _get_excluded_modules() {
            return array('wcredit');
        }
	}
}


$GLOBALS['birchschedule'] = Birchschedule::get_instance();
