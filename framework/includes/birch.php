<?php

if ( !function_exists( 'birch_ns' ) ) {

	class Birch_NSObject extends stdClass implements ArrayAccess {

		function __get( $key ) {
			throw new ErrorException( sprintf(
					'No sub-namespace or function <%s> is defined in namespace <%s>',
					$key, $this->ns_string ) );
		}

		function __set( $key, $value ) {
			if ( !_birch_is_valid_var_name( $key ) ) {
				throw new ErrorException( sprintf(
						'String <%s> is invalid as the sub-namespace or function name in namespace <%s>',
						$key, $this->ns_string ) );
			}
			if ( ( $key !== 'ns_string' ) && ( !is_a( $value, 'Birch_NSObject' ) && !is_callable( $value ) ) ) {

				throw new ErrorException(
					sprintf( 'Namespace <%s> can only has sub-namespaces or callables (like functions).' .
						' The given value is <%s>', $this->ns_string, $value ) );
			}
			$this->$key = $value;
		}

		function __toString() {
			return '$' . str_replace( '.', '->', $this->ns_string );
		}

		function offsetExists( $key ) {
			return isset( $this->$key );
		}

		function offsetGet( $key ) {
			return $this->$key;
		}

		function offsetSet( $key, $value ) {
			$this->$key = $value;
		}

		function offsetUnset( $key ) {
			unset( $this->$key );
		}

		public function __call( $method, $args ) {
			if ( is_callable( $this->$method ) ) {
				$func_full_name = str_replace( '.', '_', $this->ns_string ) . '_' . $method;

				$event_before = $func_full_name . '_before';
				if ( has_action( $event_before ) ) {
					do_action_ref_array( $event_before, $args );
				}

				$result = call_user_func_array( $this->$method, $args );
				if ( has_filter( $func_full_name ) ) {
					$new_args = array_merge( array( $result ), $args );
					$filter_args = array_merge( array( $func_full_name ), $new_args );
					$result = call_user_func_array( 'apply_filters', $filter_args );
				}

				$event_after = $func_full_name . '_after';
				if ( has_action( $event_after ) ) {
					do_action_ref_array( $event_after, array_merge( $args, array( $result ) ) );
				}
				return $result;
			} else {
				throw new ErrorException( sprintf( "Method <%s> doesn't exist in namespace <%s>.", $method, $this->ns_string ) );
			}
		}
	}

	function _birch_is_valid_var_name( $name ) {
		return preg_match( '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name );
	}

	function _birch_create_ns( $ns_string, $ns ) {
		if ( !is_a( $ns, 'Birch_NSObject' ) ) {
			$ns = new Birch_NSObject();
		}
		$ns->ns_string = $ns_string;
		return $ns;
	}

	function birch_ns( $ns_name ) {
		birch_assert( is_string( $ns_name ), sprintf( 'The namespace <%s> should be string.', $ns_name ) );

		$ns = explode( '.', $ns_name );
		$current_str = $ns[0];
		if ( isset( $GLOBALS[$current_str] ) ) {
			$GLOBALS[$current_str] = _birch_create_ns( $current_str, $GLOBALS[$current_str] );
		} else {
			$GLOBALS[$current_str] = _birch_create_ns( $current_str, false );
		}
		$current = $GLOBALS[$current_str];
		$subs = array_slice( $ns, 1 );
		foreach ( $subs as $sub ) {
			$current_str .= '.' . $sub;
			if ( isset( $current[$sub] ) ) {
				$current[$sub] = _birch_create_ns( $current_str, $current[$sub] );
			} else {
				$current[$sub] = _birch_create_ns( $current_str, false );
			}
			$current = $current[$sub];
		}
		return $current;
	}

	function birch_defn( $ns, $func_name, $func ) {
		$ns[$func_name] = $func;
	}

	function birch_assert( $assertion, $message = '' ) {
		if ( !$assertion ) {
			throw new ErrorException( $message );
		}
	}

	function birch_log() {
		$args = func_get_args();
		$message = '';
		foreach ( $args as $arg ) {
			if ( is_array( $arg ) || is_object( $arg ) ) {
				$message .= print_r( $arg, true );
			} else {
				$message .= $arg;
			}
		}
		error_log( $message );
	}

}
