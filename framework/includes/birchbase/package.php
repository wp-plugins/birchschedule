<?php

function birchbase_def() {

	global $birchbase;

	$ns = birch_ns('birchbase');

	$version = '';

	birch_defn($ns, 'set_version', function($new_version) use (&$version) {
		$version = $new_version;
	});

	birch_defn($ns, 'get_version', function() use ($version) {
		return $version;
	});

	birch_defn($ns, 'load_package', function($dir) use($ns) {
        if (is_dir($dir)) {
            $package_file = $dir . '/package.php';
            if (is_file($package_file)) {
                include_once $package_file;
            }
            $sub_packages = scandir($dir);
            if ($sub_packages) {
                foreach ($sub_packages as $sub_package) {
                    if ($sub_package != '.' && $sub_package != '..') {
                        $sub_package_dir = $dir . '/' . $sub_package;
                        if(is_dir($sub_package_dir)) {
                            $ns->load_package($sub_package_dir);
                        }
                    }
                }
            }
        }
	});

}

birchbase_def();