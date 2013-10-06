<?php

if(!class_exists('Birchpress')) {

	function birch_assert($assertion, $message = '') {
		if(!$assertion) {
			throw new ErrorException($message);
		}
	}

	final class Birchpress {
		private static $instance;

		private function __construct() {
		}

        public function define_function($package, $fn, $imp = "") {
            $this->lang->define_function($package, $fn, $imp);
        }

        public function define_multimethod($package, $multi, 
        	$subject = "subject", $options = array()) {

			$this->lang->define_multimethod($package, $multi, $subject, $options);        	
        }

        public function define_method($package, $multi, $match_value, $imp = '') {
        	$this->lang->define_method($package, $multi, $match_value, $imp);
		}

        public function dispatch($package, $method, $arguments) {
        	return $this->lang->dispatch($package, $method, $arguments);
        }

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchpress();
			}
			return self::$instance;
		}
	}

	$GLOBALS['birchpress'] = Birchpress::get_instance();

}