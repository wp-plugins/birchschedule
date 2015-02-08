<?php

if ( !function_exists( 'birchpress_load' ) ) {

	function birchpress_load() {

		require_once 'birch.php';

		if(!class_exists('lessc')) {
		    require_once dirname(__FILE__) . '/../lib/lessphp/lessc.inc.php';
		}

		require_once 'birchpress/package.php';
		require_once 'birchpress/util/package.php';
		require_once 'birchpress/db/package.php';
		require_once 'birchpress/view/package.php';

		global $birchpress;

		$birchpress->set_version( '0.8' );

		$birchpress->init_package( $birchpress );

	}

	birchpress_load();

}
