<?php

if (!class_exists('Birchschedule')) {

    class Birchschedule extends Birchpress_Lang_Package {
		private static $instance;

        private $plugin_url;

        private $plugin_file_path;

        private $app;

        private function __construct() {
            $this->app = new Birchpress_Lang_App();
        }

        public function define_interface() {

            $this->define_function('init_modules', 
                array($this, '_init_modules'));

            $this->define_function('define_modules_interfaces', 
                array($this, '_define_modules_interfaces'));

            $this->define_function('load_modules', 
                array($this, '_load_modules'));

            $this->define_function('define_modules_lookups_tables', 
                array($this, '_define_modules_lookups_tables'));

            $this->define_function('upgrade_core', 
                array('Birchschedule_Upgrader', 'upgrade_core'));

            $this->define_multimethod('upgrade_module', 'module');
            $this->define_method('upgrade_module', 'default_', 
                array($this, '_upgrade_module'));
        }

        public function set_plugin_file_path($plugin_file_path) {
            $this->plugin_file_path = $plugin_file_path;
            $this->plugin_url = 
                plugins_url(basename(plugin_dir_path($plugin_file_path)), 
                    basename($plugin_file_path));
        }

        public function plugin_url() {
            return $this->plugin_url;
        }

        public function plugin_file_path() {
            return $this->plugin_file_path;
        }

		public static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchschedule();
			}
			return self::$instance;
		}

        public function add_module($key, $module) {
            $this->app->add_module($key, $module);
        }

        public function get_module($key) {
            return $this->app->get_module($key);
        }

        public function _load_modules() {
            $modules_dir = plugin_dir_path($this->plugin_file_path) . 'modules';
            $this->app->load_modules($modules_dir);
        }

        public function _define_modules_interfaces() {
            $this->app->define_modules_interfaces();
        }

        public function _define_modules_lookups_tables() {
            $this->app->define_modules_lookups_tables();
        }

        public function get_module_codes() {
            return $this->app->get_module_codes();
        }

        public function upgrade() {
            $this->upgrade_core();
            $modules_codes = $this->app->get_module_codes();
            foreach($modules_codes as $module_code) {
                $this->upgrade_module(array(
                    'module' => $module_code
                ));
            }
        }

        public function _upgrade_module() {}
        
        public function _init_modules() {
            $this->app->init_modules();
        }

    }

    $GLOBALS['birchschedule'] = Birchschedule::get_instance();

    require_once 'upgrader.php';

}
