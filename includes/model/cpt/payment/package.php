<?php

birch_ns( 'birchschedule.model.cpt.payment', function( $ns ) {

		global $birchschedule;

		birch_defn( $ns, 'init', function() use ( $ns, $birchschedule ) {

				birch_defmethod( $birchschedule->model, 'pre_save', 'birs_payment', $ns->pre_save );
				birch_defmethod( $birchschedule->model, 'post_get', 'birs_payment', $ns->post_get );
			} );

		birch_defn( $ns, 'pre_save', function( $payment, $config ) {
				birch_assert( is_array( $payment ) && isset( $payment['post_type'] ) );

				if ( isset( $payment['_birs_payment_amount'] ) ) {
					$payment['_birs_payment_amount'] = floatval( $payment['_birs_payment_amount'] );
				}
				return $payment;
			} );

		birch_defn( $ns, 'post_get', function( $payment ) {
				birch_assert( is_array( $payment ) && isset( $payment['post_type'] ) );
				$payment['_birs_payment_amount'] = floatval( $payment['_birs_payment_amount'] );
				return $payment;
			} );

	} );
