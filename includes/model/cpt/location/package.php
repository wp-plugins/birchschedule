<?php

birch_ns( 'birchschedule.model.cpt.location', function( $ns ) {

		global $birchschedule;

		birch_defn( $ns, 'init', function() use ( $ns, $birchschedule ) {

				birch_defmethod( $birchschedule->model, 'pre_save', 'birs_location', $ns->pre_save );
				birch_defmethod( $birchschedule->model, 'post_get', 'birs_location', $ns->post_get );
			} );

		birch_defn( $ns, 'pre_save', function( $location, $config ) {
				birch_assert( is_array( $location ) && isset( $location['post_type'] ) );
				return $location;
			} );

		birch_defn( $ns, 'post_get', function( $location ) {
				birch_assert( is_array( $location ) && isset( $location['post_type'] ) );
				if ( isset( $location['post_title'] ) ) {
					$location['_birs_location_name'] = $location['post_title'];
				}
				if ( isset( $location['post_content'] ) ) {
					$location['_birs_location_description'] = $location['post_content'];
				}
				return $location;
			} );

	} );
