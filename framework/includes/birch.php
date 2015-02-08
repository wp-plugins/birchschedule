<?php

if ( !function_exists( 'birch_ns' ) ) {

	class Birch_Fn extends stdClass {
		var $fn;
		var $ns;
		var $name;

		function __construct( $config ) {
			$this->fn = $config['fn'];
			$this->ns = $config['ns'];
			$this->name = $config['name'];
		}

		function get_message_name() {
			return str_replace( '.', '_', $this->ns->ns_string ) . '_' . $this->name;
		}

		function get_call_string() {
			return '$' . str_replace( '.', '->', $this->ns->ns_string ) . '->' . $this->name;
		}

		function find_real_function( $args ) {
			return $this->fn;
		}

		function __invoke() {
			$args = func_get_args();
			$message_name = $this->get_message_name();

			$event_before = $message_name . '_before';
			if ( has_action( $event_before ) ) {
				do_action_ref_array( $event_before, $args );
			}

			$event_pre = $message_name . '_args';
			if ( has_filter( $event_pre ) ) {
				$args = apply_filters_ref_array( $event_pre, $args );
			}

			$result = null;
			$fn = $this->find_real_function( $args );
			if ( is_callable( $fn ) ) {
				$result = call_user_func_array( $fn, $args );
			}
			if ( has_filter( $message_name ) ) {
				$new_args = array_merge( array( $result ), $args );
				$result = apply_filters_ref_array( $message_name, $new_args );
			}

			$event_after = $message_name . '_after';
			if ( has_action( $event_after ) ) {
				do_action_ref_array( $event_after, array_merge( $args, array( $result ) ) );
			}

			return $result;
		}

		function __toString() {
			return $this->get_call_string();
		}
	}

	class Birch_Multi extends Birch_Fn {
		var $fns;
		var $fn_lookup;

		function __construct( $config ) {
			parent::__construct( $config );
			$this->fns = array();
			if ( is_callable( $this->fn ) ) {
				$this->fns['_root'] = $this->fn;
			} else {
				$this->fns['_root'] = function() {};
			}
			$this->fn_lookup = $config['fn_lookup'];
		}

		function find_real_function( $args ) {

			$lookup_options = call_user_func( $this->fn_lookup );
			if ( !is_array( $lookup_options ) ) {
				throw new ErrorException( sprintf(
						'Function %s should return array.', $this->fn_lookup ) );
			}

			if ( !empty( $lookup_options['key'] ) ) {
				$key = $lookup_options['key'];
			} else {
				$key = 'type';
			}

			if ( !empty( $lookup_options['lookup_table'] ) ) {
				$lookup_table = $lookup_options['lookup_table'];
			} else {
				$lookup_table = array();
			}

			if ( empty( $args ) || !is_array( $args[0] ) || empty( $args[0][$key] ) ) {
				throw new ErrorException( sprintf(
						'The first argument of Multimethod %s should be array and contain %s',
						$this->get_call_string(), $key ) );
			}
			$match_value = $args[0][$key];

			$fns = $this->fns;
			if ( isset( $lookup_table[$match_value] ) ) {
				$lookup_chain = $lookup_table[$match_value];
				foreach ( $lookup_chain as $value ) {
					if ( isset( $fns[$value] ) ) {
						$real_function = $fns[$value];
						return $real_function;
					}
				}
			} else {
				if ( isset( $fns[$match_value] ) ) {
					$real_function = $fns[$match_value];
					return $real_function;
				}
			}
			$real_function = $fns['_root'];
			return $real_function;
		}
	}

	class Birch_NSObject extends stdClass implements ArrayAccess {

		private $sub_ns_keys = array();

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
			if ( ( $key !== 'ns_string' ) && ( !is_a( $value, 'Birch_NSObject' ) && !is_a( $value, 'Birch_Fn' ) ) ) {

				throw new ErrorException(
					sprintf( 'Namespace <%s> can only has instances of the Birch_NSObject or Birch_Fn classes.' .
						' The given value is <%s>', $this->ns_string, $value ) );
			}
			$this->$key = $value;
			if ( is_a( $value, 'Birch_NSObject' ) ) {
				$pos = strpos( $value->ns_string, $this->ns_string );
				if ( $pos === false || $pos !== 0 ) {
					throw new ErrorException(
						sprintf( 'Namespace <%s> is not a sub namespace of namespace <%s>',
							$value->ns_string, $this->ns_string ) );
				}
				$this->sub_ns_keys[] = $key;
			}
		}

		function get_sub_ns_keys() {
			return $this->sub_ns_keys;
		}

		function __toString() {
			return $this->ns_string;
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

		function __call( $method, $args ) {
			return call_user_func_array( $this->$method, $args );
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

	function birch_ns( $ns_name, $init_func = false ) {
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
		if ( is_callable( $init_func ) ) {
			global $birch_current_ns;

			$birch_current_ns = $current;
			$init_func( $current );
			$birch_current_ns = false;
		}
		return $current;
	}

	function birch_defn( $ns, $func_name, $func ) {
		global $birch_current_ns;

		if ( !is_a( $ns, 'Birch_NSObject' ) ) {
			throw new ErrorException( sprintf(
					'<%s> is not a namespace object.',
					$ns ) );
		}
		if ( !empty( $birch_current_ns ) && strval( $birch_current_ns ) != strval( $ns ) ) {
			throw new ErrorException( sprintf(
					"<%s> should be the same with current namespace <%s>",
					$ns, $birch_current_ns ) );

		}
		if ( !_birch_is_valid_var_name( $func_name ) ) {
			throw new ErrorException( sprintf(
					'String <%s> is invalid as the function name.',
					$func_name ) );
		}
		if ( !is_callable( $func ) ) {
			throw new ErrorException( sprintf(
					'<%s> is not callable.',
					$func ) );
		}
		if ( empty( $birch_current_ns ) && !is_a( $func, 'Birch_Fn' ) ) {
			throw new ErrorException( sprintf(
					"<%s>(last arg) must be a Birch_Fn(hookable function) to redefine function.",
					print_r( $func, true ) ) );
		}
		$config = array(
			'ns' => $ns,
			'name' => $func_name,
			'fn' => $func
		);
		$ns[$func_name] = new Birch_Fn( $config );
	}

	function birch_defmulti( $ns, $multi_name, $fn_lookup, $func ) {
		global $birch_current_ns;

		if ( !empty( $birch_current_ns ) && strval( $birch_current_ns ) != strval( $ns ) ) {
			throw new ErrorException( sprintf(
					"<%s> should be the same with current namespace <%s>",
					$ns, $birch_current_ns ) );

		}
		if ( !is_a( $ns, 'Birch_NSObject' ) ) {
			throw new ErrorException( sprintf(
					'<%s> is not a namespace object.',
					$ns ) );
		}
		if ( !is_callable( $func ) ) {
			throw new ErrorException( sprintf(
					'<%s> is not callable.',
					$func ) );
		}
		if ( !_birch_is_valid_var_name( $multi_name ) ) {
			throw new ErrorException( sprintf(
					'String <%s> is invalid as the function name.',
					$multi_name ) );
		}
		if ( !is_callable( $fn_lookup ) ) {
			throw new ErrorException( sprintf(
					'<%s> is not callable.',
					$fn_lookup ) );
		}
		if ( empty( $birch_current_ns ) && !is_a( $func, 'Birch_Fn' ) ) {
			throw new ErrorException( sprintf(
					"<%s>(last arg) must be a Birch_Fn(hookable function) to redefine function.",
					print_r( $func, true ) ) );
		}
		$config = array(
			'ns' => $ns,
			'name' => $multi_name,
			'fn' => $func,
			'fn_lookup' => $fn_lookup
		);
		$ns[$multi_name] = new Birch_Multi( $config );
	}

	function birch_defmethod( $ns, $multi_name, $match_value, $func ) {
		global $birch_current_ns;

		if ( !empty( $birch_current_ns ) && strval( $birch_current_ns ) != strval( $ns ) ) {
			throw new ErrorException( sprintf(
					"<%s> should be the same with current namespace <%s>",
					$ns, $birch_current_ns ) );

		}
		if ( empty( $ns->$multi_name ) || !is_a( $ns->$multi_name, 'Birch_Multi' ) ) {
			throw new ErrorException( sprintf(
					'<%s> is not defined as multimethod in namespace <%s>.',
					$multi_name, $ns ) );
		}
		if ( !is_a( $ns, 'Birch_NSObject' ) ) {
			throw new ErrorException( sprintf(
					'<%s> is not a namespace object.',
					$ns ) );
		}
		if ( !is_a( $func, 'Birch_Fn' ) ) {
			throw new ErrorException( sprintf(
					'<%s>(last arg) must be a Birch_Fn(hookable function) to define method.',
					print_r( $func, true ) ) );
		}
		$multi = $ns->$multi_name;
		$multi->fns[$match_value] = $func;
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
