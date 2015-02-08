<?php

birch_ns( 'birchschedule', function( $ns ) {

		$plugin_url = '';

		$plugin_file_path = '';

		$less_dirs = array();

		$module_names = array();

		$product_version = '';

		$product_name = '';

		$product_code = '';

		birch_defn( $ns, 'set_product_version', function( $_product_version ) use ( $ns, &$product_version ) {
				$product_version = $_product_version;
			} );

		birch_defn( $ns, 'get_product_version', function() use ( $ns, &$product_version ) {
				return $product_version;
			} );

		birch_defn( $ns, 'set_product_name', function( $_product_name ) use ( $ns, &$product_name ) {
				$product_name = $_product_name;
			} );

		birch_defn( $ns, 'get_product_name', function() use ( $ns, &$product_name ) {
				return $product_name;
			} );

		birch_defn( $ns, 'set_product_code', function( $_product_code ) use ( $ns, &$product_code ) {
				$product_code = $_product_code;
			} );

		birch_defn( $ns, 'get_product_code', function() use ( $ns, &$product_code ) {
				return $product_code;
			} );

		birch_defn( $ns, 'set_plugin_file_path', function ( $_plugin_file_path )
			use( $ns, &$plugin_url, &$plugin_file_path ) {

				$plugin_file_path = $_plugin_file_path;
				$plugin_url = plugins_url() . '/' . basename( $plugin_file_path, '.php' );
			} );

		birch_defn( $ns, 'plugin_url', function() use ( $ns, &$plugin_url ) {
				return $plugin_url;
			} );

		birch_defn( $ns, 'plugin_file_path', function() use ( $ns, &$plugin_file_path ) {
				return $plugin_file_path;
			} );

		birch_defn( $ns, 'plugin_dir_path', function () use ( $ns, &$plugin_file_path ) {
				return plugin_dir_path( $plugin_file_path );
			} );

		birch_defn( $ns, 'add_less_dir', function( $less_dir ) use( $ns, &$less_dirs ) {
				$less_dirs[] = $less_dir;
			} );

		birch_defn( $ns, 'compile_less', function() use ( $ns, &$less_dirs ) {
				foreach ( $less_dirs as $less_dir ) {
					$ns->compile_less_dir( $less_dir );
				}
			} );

		birch_defn( $ns, 'compile_less_dir', function( $dir ) use ( $ns ) {
				global $birchpress;

				$less = new lessc();
				if ( is_dir( $dir ) ) {
					$files = scandir( $dir );
					if ( $files ) {
						foreach ( $files as $file ) {
							if ( $file != '.' && $file != '..' ) {
								if ( is_dir( $dir . '/' . $file ) ) {
									$ns->compile_less_dir( $dir . '/' . $file );
								} else {
									if ( $birchpress->util->ends_with( $file, '.less' ) ) {
										$input_less = $dir . "/$file";
										$output_less = substr( $input_less, 0, strlen( $input_less ) - 4 ) . 'css';
										$less->checkedCompile( $input_less, $output_less );
									}
								}
							}
						}
					}
				}
			} );

		birch_defn( $ns, 'load_core', function() use( $ns ) {
				global $birchpress;

				$core_dir = $ns->plugin_dir_path() . 'includes';
				$packages = array( 'model', 'view', 'upgrader' );
				foreach ( $packages as $package ) {
					$birchpress->load_package( $core_dir . '/' . $package );
				}
			} );

		birch_defn( $ns, 'load_modules', function() use ( $ns, &$module_names ) {
				global $birchpress;

				$modules_dir = $ns->plugin_dir_path() . 'modules';
				$_module_names = scandir( $modules_dir );
				foreach ( $_module_names as $module_name ) {
					if ( $module_name != '.' && $module_name != '..' ) {

						$module_names[] = $module_name;
						$module_dir = $modules_dir . '/' . $module_name;
						$birchpress->load_package( $module_dir );
					}
				}
			} );

		birch_defn( $ns, 'upgrade_core', function() {} );

		birch_defn( $ns, 'get_module_lookup_config', function() {
				return array(
					'key' => 'module',
					'lookup_table' => array()
				);
			} );

		birch_defmulti( $ns, 'upgrade_module', $ns->get_module_lookup_config, function( $module_a ) {} );

		birch_defn( $ns, 'upgrade', function() use ( $ns, &$module_names ) {
				$ns->upgrade_core();
				foreach ( $module_names as $module_name ) {
					$ns->upgrade_module( array(
							'module' => $module_name
						) );
				}
			} );

		birch_defn( $ns, 'init_packages', function() use ( $ns ) {
				global $birchpress;

				$birchpress->init_package( $ns );
			} );

		birch_defn( $ns, 'run', function() use( $ns ) {
				global $birchpress;

				$ns->load_core();
				$ns->load_modules();
				$ns->init_packages();
				$ns->upgrade();
			} );

		birch_defn($ns, 'spawn_cron', function() {
			spawn_cron();
		});
		
	} );
