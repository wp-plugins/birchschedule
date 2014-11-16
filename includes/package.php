<?php

if(!class_exists('Birchschedule')) {

	final class Birchschedule extends Birchpress_Lang_Plugin {

		public function define_interface() {
			parent::define_interface();
            $this->define_function('upgrade_core', 
                array('Birchschedule_Upgrader', 'upgrade_core'));

		}

        public function _get_excluded_modules() {
            return array('wcredit');
        }

        public function run() {
        	global $birchpress;

			$birchpress->util->define_interface();
			$birchpress->db->define_interface();
			$birchpress->view->define_interface();
			$birchpress->view->init();
        	parent::run();
        }
	}

	$GLOBALS['birchschedule'] = new Birchschedule();

}

