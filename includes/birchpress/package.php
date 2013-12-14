<?php

if(!class_exists('Birchpress')) {

	function birch_assert($assertion, $message = '') {
		if(!$assertion) {
			throw new ErrorException($message);
		}
	}

    function birch_log() {
        $args = func_get_args();
        $message = '';
        foreach ($args as $arg) {
            if (is_array($arg) || is_object($arg)) {
                $message .= print_r($arg, true);
            } else {
                $message .= $arg;
            }
        }
        error_log($message);
    }

    function birch_debug_mode($display_errors = true, $log_errors = true) {
        error_reporting( E_ALL );
        
        if ( $display_errors ) {
            ini_set( 'display_errors', 1 );
        } else {
            ini_set( 'display_errors', 0 );
        }

        if ( $log_errors ) {
             ini_set( 'log_errors', 1 );
             ini_set( 'error_log', WP_CONTENT_DIR . '/debug.log' );
        }
    }
    
	final class Birchpress {
		private static $instance;

		private function __construct() {
		}

		static function get_instance() {
			if(!self::$instance) {
				self::$instance = new Birchpress();
			}
			return self::$instance;
		}
	}

	$GLOBALS['birchpress'] = Birchpress::get_instance();

}