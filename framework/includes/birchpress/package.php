<?php

birch_ns( 'birchpress', function( $ns ) {

        $version = '';

        $framework_url = '';

        birch_defn( $ns, 'set_version', function( $new_version ) use ( &$version ) {
                $version = $new_version;
            } );

        birch_defn( $ns, 'get_version', function() use ( &$version ) {
                return $version;
            } );

        birch_defn( $ns, 'set_plugin_url', function( $plugin_url ) use ( &$framework_url ) {
                $framework_url = $plugin_url . '/framework';
            } );

        birch_defn( $ns, 'get_framework_url', function() use ( &$framework_url ) {
                return $framework_url;
            } );

        birch_defn( $ns, 'load_package', function( $dir ) use( $ns ) {
                if ( is_dir( $dir ) ) {
                    $package_file = $dir . '/package.php';
                    if ( is_file( $package_file ) ) {
                        include_once $package_file;
                    }
                    $sub_packages = scandir( $dir );
                    if ( $sub_packages ) {
                        foreach ( $sub_packages as $sub_package ) {
                            if ( $sub_package != '.' && $sub_package != '..' ) {
                                $sub_package_dir = $dir . '/' . $sub_package;
                                if ( is_dir( $sub_package_dir ) ) {
                                    $ns->load_package( $sub_package_dir );
                                }
                            }
                        }
                    }
                }
            } );

        birch_defn( $ns, 'init_package', function( $package ) use ( $ns ) {
                if ( !empty( $package->init ) && is_callable( $package->init ) ) {
                    $package->init();
                }
                $sub_ns_keys = $package->get_sub_ns_keys();
                foreach ( $sub_ns_keys as $key ) {
                    $sub_package = $package[$key];
                    $ns->init_package( $sub_package );
                }
            } );
    } );
