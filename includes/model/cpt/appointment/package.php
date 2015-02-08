<?php

birch_ns( 'birchschedule.model.cpt.appointment', function( $ns ) {

		global $birchschedule;

		birch_defn( $ns, 'init', function() use ( $ns, $birchschedule ) {

				birch_defmethod( $birchschedule->model, 'pre_save', 'birs_appointment', $ns->pre_save );
				birch_defmethod( $birchschedule->model, 'post_get', 'birs_appointment', $ns->post_get );
			} );

		birch_defn( $ns, 'pre_save', function( $appointment, $config ) {
				birch_assert( is_array( $appointment ) && isset( $appointment['post_type'] ) );
				global $birchschedule;

				if ( isset( $appointment['_birs_appointment_duration'] ) ) {
					$appointment['_birs_appointment_duration'] = (int) $appointment['_birs_appointment_duration'];
				}
				return $appointment;
			} );

		birch_defn( $ns, 'post_get', function( $appointment ) {
				birch_assert( is_array( $appointment ) && isset( $appointment['post_type'] ) );
				global $birchpress;

				if ( isset( $appointment['_birs_appointment_timestamp'] ) ) {
					$timestamp = $appointment['_birs_appointment_timestamp'];
					$appointment['_birs_appointment_datetime'] =
					$birchpress->util->convert_to_datetime( $timestamp );
				}
				if ( !isset( $appointment['appointment1on1s'] ) ) {
					$appointment['appointment1on1s'] = array();
				}
				return $appointment;
			} );

	} );
