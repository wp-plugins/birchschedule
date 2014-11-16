<?php

if ( !function_exists( 'birchbase_load' ) ) {

	function birchbase_load() {

		require_once 'birch.php';

		require_once 'birchbase/package.php';
		require_once 'birchbase/util/package.php';
		require_once 'birchbase/db/package.php';
		require_once 'birchbase/view/package.php';

		global $birchbase;

		$birchbase->set_version( '0.5' );
	}

	birchbase_load();

}
