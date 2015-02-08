<?php

birch_ns( 'birchpress.view', function( $ns ) {

        $scripts_data = array();

        $enqueued_scripts = array();

        $localized_scripts = array();

        $printed_scripts = array();

        birch_defn( $ns, 'init', function() use ( $ns ) {
                add_action( 'init', array( $ns, 'wp_init' ) );
                add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
            } );

        birch_defn( $ns, 'wp_init', function() use ( $ns ) {
                if ( !defined( 'DOING_AJAX' ) ) {

                    $ns->register_core_scripts();

                    add_action( 'wp_print_scripts',
                        array( $ns, 'localize_scripts' ) );

                    if ( is_admin() ) {
                        add_action( 'admin_print_footer_scripts',
                            array( $ns, 'localize_scripts' ), 9 );
                        add_action( 'admin_print_footer_scripts',
                            array( $ns, 'post_print_scripts' ), 11 );
                    } else {
                        add_action( 'wp_print_footer_scripts',
                            array( $ns, 'localize_scripts' ), 9 );
                        add_action( 'wp_print_footer_scripts',
                            array( $ns, 'post_print_scripts' ), 11 );
                    }
                }
            } );

        birch_defn( $ns, 'wp_admin_init', function() use ( $ns ) {
                add_action( 'load-post.php', function() use ( $ns ) {
                        $post_type = $ns->get_current_post_type();
                        $ns->load_post_edit( array(
                                'post_type' => $post_type
                            ) );
                    } );
                add_action( 'load-post-new.php', function() use ( $ns ) {
                        $post_type = $ns->get_current_post_type();
                        $ns->load_post_new( array(
                                'post_type' => $post_type
                            ) );
                    } );
                add_action( 'admin_enqueue_scripts', function( $hook ) use ( $ns ) {
                        $post_type = $ns->get_current_post_type();
                        if ( $hook == 'post-new.php' ) {
                            $ns->enqueue_scripts_post_new( array(
                                    'post_type' => $post_type
                                ) );
                        }
                        if ( $hook == 'post.php' ) {
                            $ns->enqueue_scripts_post_edit( array(
                                    'post_type' => $post_type
                                ) );
                        }
                        if ( $hook == 'edit.php' && isset( $_GET['post_type'] ) ) {
                            $post_type = $_GET['post_type'];
                            $ns->enqueue_scripts_post_list( array(
                                    'post_type' => $post_type
                                ) );
                        }
                    } );
                add_action( 'save_post', function( $post_id, $post ) use ( $ns ) {
                        if ( !isset( $_POST['action'] ) || $_POST['action'] !== 'editpost' ) {
                            return;
                        }
                        if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) )
                        return;
                        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
                        return;
                        if ( is_int( wp_is_post_revision( $post ) ) )
                        return;
                        if ( is_int( wp_is_post_autosave( $post ) ) )
                        return;

                        $post_a = (array)$post;
                        $ns->save_post( $post_a );
                    }, 10, 2 );
                add_filter( 'wp_insert_post_data', function( $post_data, $post_attr ) use ( $ns ) {
                        if ( !isset( $_POST['action'] ) || $_POST['action'] !== 'editpost' ) {
                            return $post_data;
                        }

                        if ( $post_data['post_status'] == 'auto-draft' ) {
                            return $post_data;
                        }
                        return $ns->pre_save_post( $post_data, $post_attr );
                    }, 10, 2 );
            } );

        birch_defn( $ns, 'get_post_type_lookup_config', function() {
                return array(
                    'key' => 'post_type',
                    'lookup_table' => array()
                );
            } );

        birch_defmulti( $ns, 'enqueue_scripts_post_new', $ns->get_post_type_lookup_config, function( $arg ) {} );

        birch_defmulti( $ns, 'enqueue_scripts_post_edit', $ns->get_post_type_lookup_config, function( $arg ) {} );

        birch_defmulti( $ns, 'enqueue_scripts_post_list', $ns->get_post_type_lookup_config, function( $arg ) {} );

        birch_defmulti( $ns, 'load_post_edit', $ns->get_post_type_lookup_config, function( $arg ) {} );

        birch_defmulti( $ns, 'load_post_new', $ns->get_post_type_lookup_config, function( $arg ) {} );

        birch_defmulti( $ns, 'save_post', $ns->get_post_type_lookup_config, function( $post_a ) {} );

        birch_defmulti( $ns, 'pre_save_post', $ns->get_post_type_lookup_config, function( $post_data, $post_attr ) {
                return $post_data;
            } );

        birch_defn( $ns, 'get_wp_screen', function( $hook_name ) use ( $ns ) {
                if ( substr( $hook_name, -4 ) !== '.php' ) {
                    $hook_name = $hook_name . '.php';
                }
                $screen = WP_Screen::get( $hook_name );
                return $screen;
            } );



        birch_defn( $ns, 'register_3rd_scripts', function() use ( $ns ) {
                global $birchpress;

                wp_register_script( 'underscore_string',
                    $birchpress->get_framework_url() . '/lib/assets/js/underscore/underscore.string.min.js',
                    array( 'underscore' ), '2.3.0' );
            } );

        birch_defn( $ns, 'register_core_scripts', function() use ( $ns ) {
                global $birchpress;

                $version = $birchpress->get_version();
                wp_register_script( 'birchpress',
                    $birchpress->get_framework_url() . '/assets/js/birchpress/base.js',
                    array( 'underscore', 'underscore_string' ), "$version" );

                wp_register_script( 'birchpress_util',
                    $birchpress->get_framework_url() . '/assets/js/birchpress/util/base.js',
                    array( 'birchpress' ), "$version" );
            } );

        birch_defn( $ns, 'get_current_post_type', function() {
                global $current_screen;

                if ( $current_screen && $current_screen->post_type ) {
                    return $current_screen->post_type;
                }

                return '';
            } );

        birch_defn( $ns, 'register_script_data_fn', function( $handle, $data_name, $fn )
            use ( $ns, &$scripts_data ) {

                if ( isset( $scripts_data[$handle] ) ) {
                    $scripts_data[$handle][$data_name] = $fn;
                } else {
                    $scripts_data[$handle] = array(
                        $data_name => $fn
                    );
                }
            } );

        birch_defn( $ns, 'enqueue_scripts', function( $scripts ) use ( $ns, &$enqueued_scripts ) {
                if ( is_string( $scripts ) ) {
                    $scripts = array( $scripts );
                }
                foreach ( $scripts as $script ) {
                    wp_enqueue_script( $script );
                }
                $enqueued_scripts = array_merge( $enqueued_scripts, $scripts );
                $enqueued_scripts = array_unique( $enqueued_scripts );
            } );

        birch_defn( $ns, 'enqueue_styles', function( $styles ) use ( $ns ) {
                if ( is_string( $styles ) ) {
                    wp_enqueue_style( $styles );
                    return;
                }
                if ( is_array( $styles ) ) {
                    foreach ( $styles as $style ) {
                        if ( is_string( $style ) ) {
                            wp_enqueue_style( $style );
                        }
                    }
                }
            } );

        birch_defn( $ns, 'localize_scripts', function() use ( $ns, &$enqueued_scripts, &$printed_scripts ) {

                global $wp_scripts;

                $wp_scripts->all_deps( $enqueued_scripts, true );
                $all_scripts = $wp_scripts->to_do;

                foreach ( $all_scripts as $script ) {
                    $ns->localize_script( $script );
                }
                $printed_scripts = $all_scripts;
            } );

        birch_defn( $ns, 'localize_script', function( $script ) use ( $ns, &$scripts_data, &$localized_scripts ) {

                if ( isset( $scripts_data[$script] ) &&
                    !in_array( $script, $localized_scripts ) ) {
                    foreach ( $scripts_data[$script] as $data_name => $data_fn ) {
                        $data = call_user_func( $data_fn );
                        wp_localize_script( $script, $data_name, $data );
                    }
                    $localized_scripts[] = $script;
                    $localized_scripts = array_unique( $localized_scripts );
                }
            } );

        birch_defn( $ns, 'post_print_scripts', function() use ( $ns, &$printed_scripts ) {
                foreach ( $printed_scripts as $script ) {
                    $ns->post_print_script( $script );
                }
            } );

        birch_defn( $ns, 'post_print_script', function( $script ) {} );

        birch_defn( $ns, 'get_screen', function( $hook_name ) use ( $ns ) {
                return $ns->get_wp_screen( $hook_name );
            } );

        birch_defn( $ns, 'get_query_array', function( $query, $keys ) {
                $source = array();
                $result = array();
                if ( is_string( $query ) ) {
                    wp_parse_str( $query, $source );
                }
                else if ( is_array( $query ) ) {
                    $source = $query;
                }
                foreach ( $keys as $key ) {
                    if ( isset( $source[$key] ) ) {
                        $result[$key] = $source[$key];
                    }
                }
                return $result;
            } );

        birch_defn( $ns, 'get_query_string', function( $query, $keys ) use ( $ns ) {
                return http_build_query( $ns->get_query_array( $query, $keys ) );
            } );

        birch_defn( $ns, 'render_ajax_success_message', function( $success ) {
?>
                <div id="birs_success" code="<?php echo $success['code']; ?>">
                    <?php echo $success['message']; ?>
                </div>
<?php
                exit;
            } );

        birch_defn( $ns, 'render_ajax_error_messages', function( $errors ) {
                global $birchpress;
                
                if ( $birchpress->util->is_errors( $errors ) ) {
                    $error_arr = array();
                    $codes = $birchpress->util->get_error_codes( $errors );
                    foreach ( $codes as $code ) {
                        $error_arr[$code] = $birchpress->util->get_error_message( $errors, $code );
                    }
                } else {
                    $error_arr = $errors;
                }
?>
                <div id="birs_errors">
                    <?php foreach ( $error_arr as $error_id => $message ): ?>
                        <div id="<?php echo $error_id; ?>"><?php echo $message; ?></div>
                    <?php endforeach; ?>
                </div>
<?php
                exit;
            } );

        birch_defn( $ns, 'render_meta_boxes', function( $config ) use ( $ns ) {
                $assert_criteria = is_array( $config ) && isset( $config['screen'] );
                birch_assert( $assert_criteria );
                $default_config = array(
                    'cols' => 1,
                    'callback_arg' => ''
                );
                $config = array_merge( $default_config, $config );
                if ( $config['cols'] === 2 ) {
?>
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="postbox-container-1" class="postbox-container">
                            <?php do_meta_boxes( $config['screen'], 'side', $config['callback_arg'] ); ?>
                        </div>
                        <div id="postbox-container-2" class="postbox-container">
                            <?php do_meta_boxes( $config['screen'], 'normal', $config['callback_arg'] ); ?>
                        </div>
                    </div>
                    <br class="clear" />
                </div>
<?php
                } else {
?>
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-1">
                        <div id="postbox-container-1" class="postbox-container">
                            <?php do_meta_boxes( $config['screen'], 'normal', $config['callback_arg'] ) ?>
                        </div>
                    </div>
                    <br class="clear" />
                </div>
<?php
                }
            } );

    } );
