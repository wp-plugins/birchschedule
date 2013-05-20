<?php

class BIRS_Payment_View {
	function __construct() {
        add_filter('birchschedule_appointment_payments_details_admin_edit',
            array($this, 'get_appointment_payments_details_html'), 10, 2);
        add_action('birchschedule_save_appointment_payments', 
        	array($this, 'save_appointment_payments'), 10, 3);
        add_action('wp_ajax_birs_add_new_appointment_payment', 
            array($this, 'ajax_add_new_appointment_payment'));
        add_filter('birchschedule_payment_types', array($this, 'get_payment_types'));
        register_post_type('birs_appointment', array(
	            'labels' => array(
	                'name' => __('Payments', 'birchschedule'),
	                'singular_name' => __('Appointment', 'birchschedule'),
	                'add_new' => __('Add Payment', 'birchschedule'),
	                'add_new_item' => __('Add New Payment', 'birchschedule'),
	                'edit' => __('Edit', 'birchschedule'),
	                'edit_item' => __('Edit Payment', 'birchschedule'),
	                'new_item' => __('New Payment', 'birchschedule'),
	                'view' => __('View Payment', 'birchschedule'),
	                'view_item' => __('View Payment', 'birchschedule'),
	                'search_items' => __('Search Payments', 'birchschedule'),
	                'not_found' => __('No Payments found', 'birchschedule'),
	                'not_found_in_trash' => __('No Payments found in trash', 'birchschedule'),
	                'parent' => __('Parent Payment', 'birchschedule')
	            ),
	            'description' => __('This is where payments are stored.', 'birchschedule'),
	            'public' => false,
	            'show_ui' => false,
	            'capability_type' => 'post',
	            'publicly_queryable' => false,
	            'exclude_from_search' => true,
	            'show_in_menu' => 'birchschedule_schedule',
	            'hierarchical' => false,
	            'show_in_nav_menus' => false,
	            'rewrite' => false,
	            'query_var' => true,
	            'supports' => array('custom-fields'),
	            'has_archive' => false
            )
        );
        $this->util = BIRS_Util::get_instance();
	}

    function get_payment_types() {
        return array(
            'credit_card' => __('Credit Card', 'birchschedule'),
            'cash' => __('Cash', 'birchschedule')
        );
    }

	function save_appointment_payments($appointment_id, $client_id, $payments) {
		foreach($payments as $payment_trid => $payment) {
			$payment_model = new BIRS_Payment(
				0, array(
						'meta_keys' => array(
							'_birs_payment_appointment', '_birs_payment_client',
							'_birs_payment_amount', '_birs_payment_type',
							'_birs_payment_trid', '_birs_payment_notes',
							'_birs_payment_timestamp', '_birs_payment_currency'
						)
					)
				);
            $payment_model->copyFromRequest($payment);
            $payment_model['_birs_payment_appointment'] = $appointment_id;
            $payment_model['_birs_payment_client'] = $client_id;
            $payment_model['_birs_payment_trid'] = $payment_trid;
            $payment_model['_birs_payment_currency'] = 
                apply_filters('birchschedule_currency_code', "USD");
            $payment_model->save();
		}
        $appointment = new BIRS_Appointment($appointment_id, array(
            'meta_keys' => array(
                '_birs_appointment_price'
            )
        ));
        $appointment->load();
        $appointment_price = $appointment['_birs_appointment_price'];
        $all_payments = $appointment->get_appointment_payments();
        $paid = 0;
        foreach($all_payments as $payment_id => $payment) {
            $paid += $payment['_birs_payment_amount'];
        }
        $payment_status = 'not-paid';
        if($paid > 0 && $appointment_price - $paid >= 0.01) {
            $payment_status = 'partially-paid';
        }
        if($paid > 0 && $appointment_price - $paid < 0.01) {
            $payment_status = 'paid';
        }
        update_post_meta($appointment_id, 
            '_birs_appointment_payment_status', $payment_status);
	}

    function get_appointment_payments_details_html($html, $appointment_id) {
        $price = 0;
        $payment_types = apply_filters('birchschedule_payment_types', array());
        $payments = array();

        if ($appointment_id) {
            $price = get_post_meta($appointment_id, '_birs_appointment_price', true);
            $appointment = new BIRS_Appointment($appointment_id);
            $payments = $appointment->get_appointment_payments();
        }
        ob_start();
        ?>
        <ul>
            <li class="birs_form_field">
                <label>
                    <?php echo apply_filters('birchschedule_price_label', __('Price', 'birchschedule')); ?>
                </label>
                <div class="birs_field_content">
                    <input type="text" id="birs_appointment_price" 
                        name="birs_appointment_price"
                        value="<?php echo $price; ?>">
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Paid', 'birchsc'); ?>
                </label>
                <div class="birs_field_content">
                    <span class="birs_money"
                        id="birs_appointment_paid"></span>
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Due', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <span class="birs_money"
                        id="birs_appointment_due"></span>
                </div>
            </li>
        </ul>
        <div class="splitter"></div>
        <ul>
            <li class="birs_form_field">
                <label>
                    <?php _e('Amount to Pay', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <input type="text" id="birs_appointment_amount_to_pay" 
                        name="birs_payment_amount"
                        value="" >
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Payment Type', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <select name="birs_payment_type">
                        <?php $this->util->render_html_options($payment_types); ?>
                    </select>
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Payment Notes', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <textarea name="birs_payment_notes"></textarea>
                </div>
            </li>
            <li class="birs_form_field">
                <div class="birs_field_content">
                    <a id="birs_add_payment"
                       class="button" href="javascript:void(0);">
                        <?php _e('Add Payment', 'birchschedule'); ?>
                    </a>
                </div>
            </li>
        </ul>
        <div class="splitter"></div>
        <ul>
            <li class="birs_form_field">
                <label>
                    <?php _e('Payment Histroy', 'birchschedule'); ?>
                </label>
            </li>
        </ul>
        <table class="wp-list-table fixed widefat" id="birs_payments_table">
            <thead>
                <tr>
                    <th><?php _e('Date', 'birchschedule'); ?></th>
                    <th class="column-author"><?php _e('Amount', 'birchschedule'); ?></th>
                    <th class="column-author"><?php _e('Type', 'birchschedule'); ?></th>
                    <th><?php _e('Notes', 'birchschedule'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach ($payments as $payment_id => $payment) {
                        $payment_datetime = 
                            $this->util->convert_to_datetime($payment['_birs_payment_timestamp']);
                        $amount = $payment['_birs_payment_amount'];
                ?>
                <tr data-payment-amount="<?php echo $amount; ?>">
                    <td><?php echo $payment_datetime; ?></td>
                    <td>
                        <?php echo apply_filters('birchschedule_add_currency_symbol',
                             $payment['_birs_payment_amount'],
                             $payment['_birs_payment_currency']); ?>
                    </td>
                    <td>
                        <?php echo $payment_types[$payment['_birs_payment_type']]; ?>
                    </td>
                    <td>
                        <?php echo $payment['_birs_payment_notes']; ?>
                    </td>
                </tr>
                <?php
                    }
                ?>
            </tbody>
        </table>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    function ajax_add_new_appointment_payment() {
        $payment_types = apply_filters('birchschedule_payment_types', array());
        $timestamp = time();
        $amount = 0;
        if(isset($_POST['birs_payment_amount'])) {
            $amount = floatval($_POST['birs_payment_amount']);
        }
        $payment_type = $_POST['birs_payment_type'];
        if(isset($_POST['birs_payment_notes'])) {
            $payment_notes = $_POST['birs_payment_notes'];
        }
        $payment_trid = uniqid();
        ?>
        <tr data-payment-amount="<?php echo $amount; ?>"
            data-payment-trid="<?php echo $payment_trid; ?>" >
            <td>
                <?php echo $this->util->convert_to_datetime($timestamp); ?>
                <input type="hidden"
                     name="birs_appointment_payments[<?php echo $payment_trid ?>][birs_payment_timestamp]"
                     value="<?php echo $timestamp; ?>" />
                <div class="row-actions">
                    <span class="delete">
                        <a href="javascript:void(0);"
                            data-payment-trid="<?php echo $payment_trid; ?>">
                            <?php _e('Delete', 'birchschedule'); ?>
                        </a>
                    </span>
                </div>
            </td>
            <td>
                <?php echo apply_filters('birchschedule_price', $amount); ?>
                <input type="hidden"
                     name="birs_appointment_payments[<?php echo $payment_trid ?>][birs_payment_amount]"
                     value="<?php echo $amount; ?>" />
            </td>
            <td>
                <?php echo $payment_types[$payment_type]; ?>
                <input type="hidden"
                     name="birs_appointment_payments[<?php echo $payment_trid ?>][birs_payment_type]"
                     value="<?php echo $payment_type; ?>" />
            </td>
            <td>
                <?php echo $payment_notes; ?>
                <input type="hidden"
                     name="birs_appointment_payments[<?php echo $payment_trid ?>][birs_payment_notes]"
                     value="<?php echo $payment_notes; ?>" />
            </td>
        </tr>
        <?php
        die();
    }

}