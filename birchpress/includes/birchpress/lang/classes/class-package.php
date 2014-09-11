<?php

if (!class_exists('Birchpress_Lang_Package')) {

	abstract class Birchpress_Lang_Package {

		public function __call($name, $arguments) {
			global $birchpress;
			return $birchpress->lang->dispatch($this, $name, $arguments);
		}

		public function prefix() {
			return strtolower(get_class($this)) . '_';
		}

		public function impclass_name() {
			return get_class($this) . '_Imp';
		}

		public function get_name() {
			return '$' .  str_replace('_', '->', strtolower(get_class($this)));
		}

	    public function define_function($fn, $imp = "") {
	    	global $birchpress;
	        $birchpress->lang->define_function($this, $fn, $imp);
	    }

	    public function define_multimethod($multi, $subject = "subject", $options = array()) {
	    	global $birchpress;
			$birchpress->lang->define_multimethod($this, $multi, $subject, $options);        	
	    }

	    public function define_method($multi, $match_value, $imp = '') {
	    	global $birchpress;
	    	$birchpress->lang->define_method($this, $multi, $match_value, $imp);
		}

		public function define_lookups($subject, $lookups) {
			global $birchpress;
			$birchpress->lang->define_lookups($this, $subject, $lookups);
		}

		public function get_lookups($subject) {
			global $birchpress;
			return $birchpress->lang->get_lookups($this, $subject);
		}

		public function define_lookups_table() {}

		public function define_interface() {}

	}
	
}