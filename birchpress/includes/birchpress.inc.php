<?php

require_once 'birchpress/package.php';
require_once 'birchpress/lang/package.php';
if(!class_exists('lessc')) {
    require_once 'lessphp/lessc.inc.php';
}
require_once 'birchpress/util/package.php';
require_once 'birchpress/db/package.php';
require_once 'birchpress/view/package.php';

if(!function_exists('birchpress_load')) {
	function birchpress_load() {
		global $birchpress;

		$birchpress->util->define_interface();
		$birchpress->db->define_interface();
		$birchpress->view->define_interface();

		$birchpress->view->init();
	}
	birchpress_load();
}

