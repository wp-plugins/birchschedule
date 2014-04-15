<?php

if (!class_exists('Birchpress_Lang_App')) {

	class Birchpress_Lang_App {

        private $modules = array();

        public function add_module($key, $module) {
            birch_assert(!isset($this->modules[$key]));
            $this->modules[$key] = $module;
        }

        public function get_module($key) {
            return $this->modules[$key];
        }

        public function load_modules($modules_dir) {
            if (is_dir($modules_dir)) {
                $modules = scandir($modules_dir);
                if ($modules) {
                    foreach ($modules as $module) {
                        if ($module != '.' && $module != '..' && $module != 'lib') {
                            $module_package_file = $modules_dir . '/' . $module . '/module.php';

                            if (is_file($module_package_file)) {
                                include_once $module_package_file;
                            }
                        }
                    }
                }
            }
            $this->calculate_dependencies();
        }

        public function define_modules_interfaces() {
            foreach($this->modules as $module) {
                if(is_a($module, 'Birchpress_Module')) {
                    $module->define_interfaces();
                }
            }
        }

        public function define_modules_lookups_tables() {
            foreach($this->modules as $module) {
                if(is_a($module, 'Birchpress_Module')) {
                    $module->define_lookups_tables();
                }
            }
        }

        public function get_module_codes() {
            $codes = array_keys($this->modules);
            return $codes;
        }

        public function init_modules() {
            foreach($this->modules as $module) {
                $module->init();
            }
        }

        private function calculate_dependencies() {
            foreach($this->modules as $module_code => $module) {
                $requires = $module->require_modules();
                foreach ($requires as $require) {
                    birch_assert(isset($this->modules[$require]), 
                        "Module [$require] required by module " .
                         "[$module_code] is missing.");
                }
            }
        }

	}

}