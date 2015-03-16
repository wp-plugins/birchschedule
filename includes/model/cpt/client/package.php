<?php

birch_ns( 'birchschedule.model.cpt.client', function( $ns ) {

		global $birchschedule;

		birch_defn( $ns, 'init', function() use ( $ns, $birchschedule ) {

				birch_defmethod( $birchschedule->model, 'pre_save', 'birs_client', $ns->pre_save );
				birch_defmethod( $birchschedule->model, 'save', 'birs_client', $ns->save );
				birch_defmethod( $birchschedule->model, 'post_get', 'birs_client', $ns->post_get );
			} );

		birch_defn( $ns, 'pre_save', function( $client, $config ) {
				birch_assert( is_array( $client ) && isset( $client['post_type'] ) );
				$name_first = '';
				$name_last = '';
				if ( isset( $client['_birs_client_name_first'] ) ) {
					$name_first = $client['_birs_client_name_first'];
				}
				if ( isset( $client['_birs_client_name_last'] ) ) {
					$name_last = $client['_birs_client_name_last'];
				}
				$client['post_title'] = $name_first . ' ' . $name_last;
				return $client;
			} );

		birch_defn( $ns, 'save', function( $client, $config ) use( $ns, $birchschedule ) {
				return $birchschedule->model->save->fns['_root']( $client, $config );
			} );

		birch_defn( $ns, 'post_get', function( $client ) {
				birch_assert( is_array( $client ) && isset( $client['post_type'] ) );
				if ( isset( $client['_birs_client_name_first'] ) &&
					isset( $client['_birs_client_name_last'] ) ) {

					$client['_birs_client_name'] =
					$client['_birs_client_name_first'] . ' ' . $client['_birs_client_name_last'];
				}
				return $client;
			} );

	} );
