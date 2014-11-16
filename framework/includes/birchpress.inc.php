<?php

if(!function_exists('birchpress_load')) {

	function birchpress_load() {
		global $birchpress;

		require_once 'birchbase.inc.php';
		
		require_once 'birchpress/package.php';
		require_once 'birchpress/lang/package.php';
		if(!class_exists('lessc')) {
		    require_once dirname(__FILE__) . '/../lib/lessphp/lessc.inc.php';
		}
		require_once 'birchpress/util/package.php';
		require_once 'birchpress/db/package.php';
		require_once 'birchpress/view/package.php';

		$birchpress->set_version('0.5');
	}

	birchpress_load();
}

