<?php

if(!class_exists('Birchpress_Lang')) {

	final class Birchpress_Lang {
		private static $instance;

        private $dispatch_map = array();

        private $hierarchy = array();

		private function __construct() {
		}

        private function is_valid_func_name($name) {
            return preg_match("/[a-zA-Z0-9_]+/", $name) === 1;
        }

        public function define_function($package, $fn, $imp = "") {
            birch_assert(is_object($package));
            birch_assert(is_string($fn));
            birch_assert($this->is_valid_func_name($fn));
            $func_name = $package->prefix() . $fn;
            if(empty($imp)) {
                $imp = array($package->impclass_name(), $fn);
            }
            $this->dispatch_map[$func_name] = array(
                    'type' => 'fn',
                    'imp' => $imp
                );
        }

        public function define_multimethod($package, $multi, 
            $subject = "subject", $options = array()) {

            birch_assert(is_object($package));
            birch_assert(is_string($multi));
            birch_assert($this->is_valid_func_name($multi));
            birch_assert(is_string($subject));
            birch_assert(is_array($options));
            $func_name = $package->prefix() . $multi;
            if(isset($options['lookups'])) {
                $lookups = $options['lookups'];
                birch_assert(is_array($lookups));
            } else {
                $lookups = array();
            }
            $this->dispatch_map[$func_name] = array(
                    'type' => 'multi',
                    'subject' => $subject,
                    'mapping' => array(
                        'default_' => array($package->impclass_name(), $multi)
                    ),
                    'lookups' => $lookups
                );
        }

        public function define_method($package, $multi, $match_value, $imp = '') {
            birch_assert(is_object($package));
            birch_assert(is_string($multi));
            birch_assert($this->is_valid_func_name($multi));
            birch_assert($this->is_valid_func_name($match_value));
            $func_name = $package->prefix() . $multi;
            birch_assert(isset($this->dispatch_map[$func_name]));
            $func_config = $this->dispatch_map[$func_name];
            birch_assert($func_config['type'] === 'multi');
            if(empty($imp)) {
                $imp = array($package->impclass_name(), $multi . '_' . $match_value);
            }
            $this->dispatch_map[$func_name]['mapping'][$match_value] = $imp;
        }

        public function define_lookups($package, $subject, $lookups) {
            $this->hierarchy[$package->prefix() . $subject] = $lookups;
        }

        public function get_lookups($package, $subject) {
            if(isset($this->hierarchy[$package->prefix() . $subject])) {
                return $this->hierarchy[$package->prefix() . $subject];
            } else {
                return array();
            }
        }

        public function dispatch($package, $method_name, $arguments) {
            birch_assert(is_object($package));
            birch_assert(is_string($method_name));
            birch_assert(is_array($arguments));
            
            $func_name = $package->prefix() . $method_name;

            if(!isset($this->dispatch_map[$func_name])) {
                $hook_name = $func_name;
                $real_function = array($package->impclass_name(), $method_name);
            } else {
                $func_config = $this->dispatch_map[$func_name];
                birch_assert(in_array($func_config['type'], array('fn', 'multi')));
                if($func_config['type'] === 'fn') {
                    $real_function = $func_config['imp'];
                    $hook_name = $func_name;
                } 
                else if($func_config['type'] === 'multi') {
                    $subject = $func_config['subject'];
                    birch_assert(isset($arguments[0]) && is_array($arguments[0]) &&
                       isset($arguments[0][$subject]), 
                       'Failed to dispatch multimethod ' . $package->get_name() . '->' . $method_name. 
                       ' on ' . print_r($arguments, true));
                    $match_value = $arguments[0][$subject];
                    $real_function = $this->find_real_function($func_config, $match_value);
                    $hook_name = $func_name . '_' . $match_value;
                }
            }

            $hook_name_before = $hook_name . '_before';
            if(has_action($hook_name_before)) {
                do_action_ref_array($hook_name_before, $arguments);
            }

            $result = $this->call_real_func_array($real_function, $arguments);

            if(has_filter($hook_name)) {
                $new_arguments = array_merge(array($result), $arguments);
                $filter_arguments = array_merge(array($hook_name), $new_arguments);
                $result = call_user_func_array('apply_filters', $filter_arguments);
            }

            $hook_name_after = $hook_name . '_after';
            if(has_action($hook_name_after)) {
                do_action_ref_array($hook_name_after, array_merge($arguments, array($result)));
            }
            return $result;
        }

        private function call_real_func_array($real_function, $arguments) {
            if(is_array($real_function) && sizeof($real_function) > 1) {
                if(is_string($real_function[0])) {
                    class_exists($real_function[0]);
                }
            }
            $result = call_user_func_array($real_function, $arguments);
            return $result;
        }

        private function find_real_function($func_config, $match_value) {
            $lookups = $func_config['lookups'];
            $mapping = $func_config['mapping'];
            if(isset($lookups[$match_value])) {
                $match_lookups = $lookups[$match_value];
                foreach($match_lookups as $lookup) {
                    if(isset($mapping[$lookup])) {
                        $real_function = $mapping[$lookup];
                        return $real_function;
                    }
                }
            } else {
                if(isset($mapping[$match_value])) {
                    $real_function = $mapping[$match_value];
                    return $real_function;
                }
            }
            $real_function = $mapping['default_'];
            return $real_function;
        }

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchpress_Lang();
			}
			return self::$instance;
		}
	}

	$GLOBALS['birchpress']->lang = Birchpress_Lang::get_instance();

    require_once "classes/class-package.php";
    require_once 'classes/class-plugin.php';

}

