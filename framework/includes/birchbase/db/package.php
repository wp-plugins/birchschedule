<?php

function birchbase_db_def() {

    $ns = birch_ns( 'birchbase.db' );

    birch_defn( $ns, 'get_base_keys', function( $keys ) {
            global $birchbase;

            $base_keys = array();
            foreach ( $keys as $key ) {
                if ( !$birchbase->util->starts_with( $key, '_' ) ) {
                    $base_keys[] = $key;
                }
            }
            return $base_keys;
        } );

    birch_defn( $ns, 'get_meta_keys', function( $keys ) {
            global $birchbase;

            $meta_keys = array();
            foreach ( $keys as $key ) {
                if ( $birchbase->util->starts_with( $key, '_' ) ) {
                    $meta_keys[] = $key;
                }
            }
            return $meta_keys;
        } );

    birch_defn( $ns, '_preprocess_config', function( $config ) use ( $ns ) {
            if ( !is_array( $config ) ) {
                $config = array();
            }

            if ( !isset( $config['base_keys'] ) ) {
                $config['base_keys'] = array();
            }

            if ( !isset( $config['meta_keys'] ) ) {
                $config['meta_keys'] = array();
            }

            if ( isset( $config['keys'] ) ) {
                $keys = $config['keys'];
                $config['base_keys'] = array_merge( $config['base_keys'], $ns->get_base_keys( $keys ) );
                $config['meta_keys'] = array_merge( $config['meta_keys'], $ns->get_meta_keys( $keys ) );
                unset( $config['keys'] );
            }
            return $config;
        } );

    birch_defn( $ns, 'get', function ( $post, $config ) use ( $ns ) {
            global $birchbase;

            $config = $ns->_preprocess_config( $config );

            if ( is_a( $post, 'WP_Post' ) ) {
                $id = $post->ID;
            } else {
                $id = $post;
                if ( !$ns->is_valid_id( $id ) ) {
                    return false;
                }
                $post = get_post( $id );
                if ( $post === null ) {
                    return false;
                }
            }

            $model = array(
                'ID' => $id
            );

            $base_keys = array_merge( array( 'post_type' ), $config['base_keys'] );

            foreach ( $base_keys as $key ) {
                if ( isset( $post->$key ) ) {
                    $model[$key] = $post->$key;
                }
            }

            $meta_keys = $config['meta_keys'];
            foreach ( $meta_keys as $key ) {
                $model[$key] = get_post_meta( $id, $key, true );
            }

            return $model;
        } );

    birch_defn( $ns, 'is_valid_id', function ( $id ) use ( $ns ) {
            return (string) (int) $id == $id && $id > 0;
        } );

    birch_defn( $ns, 'delete', function ( $id ) {
            birch_assert( (string) (int) $id == $id && $id > 0 );
            return wp_delete_post( $id );
        } );

    birch_defn( $ns, 'save', function ( $model, $config ) use ( $ns ) {
            birch_assert( is_array( $model ), 'Model should be an array.' );
            birch_assert( isset( $model['post_type'] ), 'Model should have post_type field.' );

            global $birchbase;

            $config = $ns->_preprocess_config( $config );
            $base_keys = $config['base_keys'];
            $meta_keys = $config['meta_keys'];

            $id = 0;
            if ( isset( $model['ID'] ) ) {
                if ( $ns->is_valid_id( $model['ID'] ) ) {
                    $id = $model['ID'];
                } else {
                    unset( $model['ID'] );
                }
            }
            $model_fields = array_keys( $model );
            foreach ( $model_fields as $field ) {
                if ( !in_array( $field, $base_keys ) &&
                    !in_array( $field, $meta_keys ) &&
                    $field != 'ID' && $field != 'post_type' ) {
                    unset( $model[$field] );
                }
            }
            if ( $base_keys || !$id ) {
                if ( !isset( $model['post_status'] ) ) {
                    $model['post_status'] = 'publish';
                }
                $id = wp_insert_post( $model );
            }
            if ( !$id ) {
                return false;
            }
            foreach ( $meta_keys as $key ) {
                if ( isset( $model[$key] ) ) {
                    update_post_meta( $id, $key, $model[$key] );
                }
            }
            return $id;
        } );

    birch_defn( $ns, 'get_post_columns', function() {
            return array(
                'post_author', 'post_date', 'post_date_gmt', 'post_content',
                'post_title', 'post_excerpt', 'post_status', 'comment_status',
                'ping_status', 'post_password', 'post_name', 'to_ping',
                'pinged', 'post_modified', 'post_modified_gmt', 'post_parent',
                'guid', 'menu_order', 'post_type', 'post_mime_type',
                'comment_count'
            );
        } );

    birch_defn( $ns, 'get_essential_post_columns', function( $post_type ) {
            return array(
                'post_author', 'post_date_gmt',
                'post_status',
                'post_modified_gmt',
                'post_type'
            );
        } );

    birch_defn( $ns, 'query', function ( $criteria, $config = array() ) use ( $ns ) {

            $config = $ns->_preprocess_config( $config );

            $criteria = array_merge(
                array(
                    'nopaging' => true,
                    'post_status' => 'publish',
                    'cache_results' => false,
                    'update_post_term_cache' => false,
                    'update_post_meta_cache' => false
                ),
                $criteria
            );
            if ( $criteria['nopaging'] ) {
                $criteria['no_found_rows'] = true;
            }

            if ( isset( $config['fn_get'] ) ) {
                $fn_get = $config['fn_get'];
                unset( $config['fn_get'] );
            } else {
                $fn_get = array( $ns, 'get' );
            }

            $fn_filter_posts_fields = function( $fields, $query ) use ( $ns, &$config ) {
                global $wpdb;
                $new_fields = "$wpdb->posts.ID";
                $post_columns = $ns->get_post_columns();
                foreach ( $config['base_keys'] as $key ) {
                    if ( in_array( $key, $post_columns ) ) {
                        $new_fields .= ", $wpdb->posts.$key";
                    }
                }

                return $new_fields;
            };

            $query = new WP_Query();

            $models = array();

            if ( $config['base_keys'] || $config['meta_keys'] ) {
                if ( isset( $criteria['post_type'] ) ) {
                    $post_type = $criteria['post_type'];
                } else {
                    $post_type = 'any';
                }
                $essential_keys = $ns->get_essential_post_columns( $post_type );
                if ( $criteria['cache_results'] ) {
                    $config['base_keys'] = array_merge( $essential_keys, $config['base_keys'] );
                } else {
                    $config['base_keys'] = array_merge( array( 'post_type' ), $config['base_keys'] );
                }
                $criteria['fields'] = 'custom';
                add_filter( 'posts_fields', $fn_filter_posts_fields, 20, 2 );
                $posts = $query->query( $criteria );
                remove_filter( 'posts_fields', $fn_filter_posts_fields, 20, 2 );
                foreach ( $posts as $post ) {
                    $model = call_user_func( $fn_get, $post, $config );
                    $models[$post->ID] = $model;
                }
            } else {
                $criteria['fields'] = 'ids';
                $post_ids = $query->query( $criteria );
                foreach ( $post_ids as $post_id ) {
                    $models[$post_id] = array( 'ID' => $post_id );
                }
            }
            return $models;
        } );

}

birchbase_db_def();
