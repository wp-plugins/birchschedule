<?php

if(!class_exists('Birchpress_Lang_Plugin')) {

    class Birchpress_Lang_Plugin extends Birchpress_Lang_Package {

        private $plugin_url;

        private $plugin_file_path;

        private $less_dirs;

        private $modules = array();

        private $core_packages;

        var $module_packages;

        private $module_names;

        public function __construct() {
            $this->less_dirs = array();
            $this->core_packages = array();
            $this->module_packages = array();
            $this->module_names = array();
        }

        public function register_class_loader() {
            if ( function_exists( "__autoload" ) ) {
                spl_autoload_register( "__autoload" );
            }
            spl_autoload_register( array( $this, 'autoload' ) );
        }

        public function autoload($class) {
            $class = strtolower( $class );
            $code_name = strtolower(get_class($this));
            $class_file = str_replace('_', '/', $class) . '.php';
            $class_file = str_replace($code_name, '', $class_file);

            $core_path = $this->plugin_dir_path() . 'includes/';
            $path1 = $core_path . $class_file;
            if ( is_readable( $path1 ) ) {
                include_once( $path1 );
                return;
            }

            $modules_path = $this->plugin_dir_path() . 'modules';
            $path2 = $modules_path . $class_file;
            if ( is_readable( $path2 ) ) {
                include_once( $path2 );
                return;
            }
        }

        public function define_interface() {

            $this->define_function('load_core',
                array($this, '_load_core'));

            $this->define_function('load_modules', 
                array($this, '_load_modules'));

            $this->define_function('define_modules_interfaces', 
                array($this, '_define_modules_interfaces'));

            $this->define_function('define_core_interfaces',
                array($this, '_define_core_interfaces'));

            $this->define_function('define_modules_lookups_tables', 
                array($this, '_define_modules_lookups_tables'));

            $this->define_function('init_core',
                array($this, '_init_core'));

            $this->define_function('init_modules', 
                array($this, '_init_modules'));

            $this->define_function('get_excluded_modules',
                array($this, '_get_excluded_modules'));

            $this->define_multimethod('upgrade_module', 'module');
            $this->define_method('upgrade_module', 'default_', 
                array($this, '_upgrade_module'));
        }

        public function set_plugin_file_path($plugin_file_path) {
            $this->plugin_file_path = $plugin_file_path;
            $this->plugin_url = plugins_url() . '/' . basename( $plugin_file_path, '.php');
        }

        public function plugin_url() {
            return $this->plugin_url;
        }

        public function plugin_file_path() {
            return $this->plugin_file_path;
        }

        public function plugin_dir_path() {
            return plugin_dir_path($this->plugin_file_path);
        }

        public function add_less_dir($less_dir) {
            $this->less_dirs[] = $less_dir;
        }

        public function add_module_package($package) {
            $this->module_packages[] = $package;
        }

        public function _load_modules() {
            $modules_dir = plugin_dir_path($this->plugin_file_path) . 'modules';
            $module_names = scandir($modules_dir);
            $excludes = $this->get_excluded_modules();
            foreach($module_names as $module_name) {
                if ($module_name != '.' && $module_name != '..' && 
                    !in_array($module_name, $excludes)) {

                    $this->module_names[] = $module_name;
                    $module_dir = $modules_dir . '/' . $module_name;
                    $this->_load_package($module_dir);
                }
            }
        }

        public function _define_modules_interfaces() {
            foreach($this->module_packages as $package) {
                $package->define_interface();
            }
        }

        public function _define_modules_lookups_tables() {
            foreach($this->module_packages as $package) {
                $package->define_lookups_table();
            }
        }

        public function _init_modules() {
            foreach($this->module_packages as $package) {
                $package->init();
            }
            $this->compile_less();
        }

        public function add_core_package($package) {
            $this->core_packages[] = $package;
        }

        public function _load_package($dir) {
            if (is_dir($dir)) {
                $package_file = $dir . '/package.php';

                if (is_file($package_file)) {
                    include_once $package_file;
                }
                $packages = scandir($dir);
                if ($packages) {
                    foreach ($packages as $package) {
                        if ($package != '.' && $package != '..') {
                            $package_dir = $dir . '/' . $package;
                            if(is_dir($package_dir)) {
                                $this->_load_package($package_dir);
                            }
                        }
                    }
                }
            }
        }

        public function _load_core($core_dir = false) {
            if($core_dir === false) {
                $core_dir = plugin_dir_path($this->plugin_file_path) . 'includes';
            }
            $this->_load_package($core_dir);
        }

        public function _define_core_interfaces() {
            foreach($this->core_packages as $package) {
                $package->define_interface();
            }
        }

        public function _init_core() {
            foreach($this->core_packages as $package) {
                $package->init();
            }
        }

        public function upgrade() {
            $this->upgrade_core();
            foreach($this->module_names as $module_name) {
                $this->upgrade_module(array(
                    'module' => $module_name
                ));
            }
        }

        public function _get_excluded_modules() {
            return array();
        }

        public function _upgrade_module() {}
        
        public function compile_less() {
            foreach($this->less_dirs as $less_dir) {
                $this->compile_less_dir($less_dir);
            }
        }

        public function compile_less_dir($dir) {
            global $birchpress;

            $less = new lessc();
            if (is_dir($dir)) {
                $files = scandir($dir);
                if ($files) {
                    foreach ($files as $file) {
                        if ($file != '.' && $file != '..') {
                            if(is_dir($dir . '/' . $file)) {
                                $this->compile_less_dir($dir . '/' . $file);
                            } else {
                                if($birchpress->util->ends_with($file, '.less')) {
                                    $input_less = $dir . "/$file";
                                    $output_less = substr($input_less, 0, strlen($input_less) - 4) . 'css';
                                    $less->checkedCompile($input_less, $output_less);
                                }
                            }
                        }
                    }
                }
            }
        }

        public function run() {
            $this->register_class_loader();

            $this->define_interface();

            $this->load_core();
            $this->load_modules();

            $this->define_modules_lookups_tables();

            $this->define_core_interfaces();
            $this->define_modules_interfaces();

            $this->upgrade();

            $this->init_core();
            $this->init_modules();
        }

    }

}
