<?php

class Birchschedule_View_Payments_Imp {

	private function __construct() {

	}

	static function init() {
		global $birchschedule;

		$birchschedule->view->payments->add_action('admin_init', 'wp_admin_init');
	}

	static function wp_admin_init() {
		global $birchschedule;

		self::register_post_type();
		$birchschedule->view->payments->add_action(
			'wp_ajax_birchschedule_view_payments_add_new_payment', 'ajax_add_new_payment');
	}

	static function register_post_type() {
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
        ));
	}

    static function get_payment_types() {
        return array(
            'credit_card' => __('Credit Card', 'birchschedule'),
            'cash' => __('Cash', 'birchschedule')
        );
    }

    static function get_payments_by_appointment($appointment_id) {
        global $birchschedule;

        $payments = $birchschedule->model->query(
            array(
                'post_type' => 'birs_payment',
                'meta_query' => array(
                    array(
                        'key' => '_birs_payment_appointment',
                        'value' => $appointment_id
                    )
                )
            ),
            array(
                'meta_keys' => array(
                    '_birs_payement_appointment', '_birs_payment_client',
                    '_birs_payment_amount', '_birs_payment_type',
                    '_birs_payment_trid', '_birs_payment_notes',
                    '_birs_payment_timestamp', '_birs_payment_currency'
                ),
                'base_keys' => array()
            )
        );
        return $payments;
    }

	static function save_payments($appointment_id, $client_id) {
        global $birchschedule;

        $payments = array();
        if(isset($_POST['birs_appointment_payments'])) {
            $payments = $_POST['birs_appointment_payments'];
        }
        $config = array(
            'meta_keys' => array(
                '_birs_payment_appointment', '_birs_payment_client',
                '_birs_payment_amount', '_birs_payment_type',
                '_birs_payment_trid', '_birs_payment_notes',
                '_birs_payment_timestamp', '_birs_payment_currency'
            ),
            'base_keys' => array()
        );
		foreach($payments as $payment_trid => $payment) {
			$payment_model = $birchschedule->view->merge_request(array(), $config);
            $payment_model['_birs_payment_appointment'] = $appointment_id;
            $payment_model['_birs_payment_client'] = $client_id;
            $payment_model['_birs_payment_trid'] = $payment_trid;
            $payment_model['_birs_payment_currency'] = 
                $birchschedule->model->get_currency_code();
            $payment_model['post_type'] = 'birs_payment';
            $birchschedule->model->save($payment_model, $config);
		}
        $appointment = $birchschedule->model->get($appointment_id, array(
            'meta_keys' => array(
                '_birs_appointment_price'
            ),
            'base_keys' => array()
        ));
        $appointment_price = $appointment['_birs_appointment_price'];
        $all_payments = $birchschedule->view->payments->
            get_payments_by_appointment($appointment_id);
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

    static function get_payments_details_html($appointment_id) {
    	global $birchschedule, $birchpress;

        $price = 0;
        $payment_types = $birchschedule->view->payments->get_payment_types();
        $payments = array();

        if ($appointment_id) {
            $price = get_post_meta($appointment_id, '_birs_appointment_price', true);
            $payments = $birchschedule->view->payments->
                get_payments_by_appointment($appointment_id);
        }
        ob_start();
        ?>
        <ul>
            <li class="birs_form_field">
                <label>
                    <?php 
                        $currency_code = $birchschedule->model->get_currency_code();
                        echo $birchschedule->view->
                            apply_currency_to_label(__('Price', 'birchschedule'), $currency_code); 
                    ?>
                </label>
                <div class="birs_field_content">
                    <input type="text" id="birs_appointment_price" 
                        name="birs_appointment_price"
                        value="<?php echo $price; ?>">
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Paid', 'birchschedule'); ?>
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
                        <?php $birchpress->util->render_html_options($payment_types); ?>
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
                    <?php _e('Payment History', 'birchschedule'); ?>
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
                            $birchpress->util->convert_to_datetime($payment['_birs_payment_timestamp']);
                        $amount = $payment['_birs_payment_amount'];
                ?>
                <tr data-payment-amount="<?php echo $amount; ?>">
                    <td><?php echo $payment_datetime; ?></td>
                    <td>
                        <?php echo $birchschedule->model->apply_currency_symbol(
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

    static function ajax_add_new_payment() {
        global $birchpress, $birchschedule;

        $payment_types = $birchschedule->view->payments->get_payment_types();
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
                <?php echo $birchpress->util->convert_to_datetime($timestamp); ?>
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
                <?php 
                    $currency_code = $birchschedule->model->get_currency_code();
                    echo $birchschedule->model->apply_currency_symbol($amount, $currency_code); 
                ?>
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