<?php

if (!class_exists('Birchschedule')) {

    class Birchschedule extends Birchpress_Lang_Package {
		private static $instance;

        private $plugin_url;

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

            $this->define_function('upgrade_core', 
                array('Birchschedule_Upgrader', 'upgrade_core'));

            $this->define_multimethod('upgrade_module', 'module');
            $this->define_method('upgrade_module', 'default_', 
                array($this, '_upgrade_module'));
        }

        public function plugin_url() {
            if (!$this->plugin_url) {
                $this->plugin_url = 
                    plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
            }
            return $this->plugin_url;
        }

        public function plugin_file_path() {
            return __FILE__;
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
            $modules_dir = plugin_dir_path(__FILE__) . 'modules';
            $this->app->load_modules($modules_dir);
        }

        public function _define_modules_interfaces() {
            $this->app->define_modules_interfaces();
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
