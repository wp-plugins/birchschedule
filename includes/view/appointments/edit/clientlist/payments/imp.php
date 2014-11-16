<?php

final class Birchschedule_View_Appointments_Edit_Clientlist_Payments_Imp {

    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->view->appointments->edit->clientlist->payments;
    }

	static function init() {
		add_action('admin_init', array(self::$package, 'wp_admin_init'));
        self::register_post_type();
        add_action('birchschedule_view_register_common_scripts_after', 
            array(self::$package, 'register_scripts'));
	}

	static function wp_admin_init() {
        add_action('birchschedule_view_enqueue_scripts_post_edit_birs_appointment_after',
            array(self::$package, 'enqueue_scripts_post_edit_birs_appointment'));
		add_action(
			'wp_ajax_birchschedule_view_appointments_edit_clientlist_payments_add_new_payment', 
            array(self::$package, 'ajax_add_new_payment')
        );
        add_action(
            'wp_ajax_birchschedule_view_appointments_edit_clientlist_payments_render_payments',
            array(self::$package, 'ajax_render_payments')
        );
        add_action(
            'wp_ajax_birchschedule_view_appointments_edit_clientlist_payments_make_payments',
            array(self::$package, 'ajax_make_payments')
        );
	}

	static function register_post_type() {
        register_post_type('birs_payment', array(
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
            'capability_type' => 'birs_payment',
            'map_meta_cap' => true,
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

    static function register_scripts() {
        $version = self::$birchschedule->product_version;

        wp_register_script('birchschedule_view_appointments_edit_clientlist_payments', 
            self::$birchschedule->plugin_url() . '/assets/js/view/appointments/edit/clientlist/payments/base.js', 
            array('birchschedule_view_admincommon', 'birchschedule_view'), "$version");

    }

    static function enqueue_scripts_post_edit_birs_appointment($arg) {
        birch_assert(is_array($arg) && isset($arg['post_type']) && 
            $arg['post_type'] == 'birs_appointment');

        global $birchschedule;

        $birchschedule->view->register_3rd_scripts();
        $birchschedule->view->register_3rd_styles();
        $birchschedule->view->enqueue_scripts(
            array(
                'birchschedule_view_appointments_edit_clientlist_payments'
            )
        );
    }

    static function get_payment_types() {
        return self::$birchschedule->model->booking->get_payment_types();
    }

	static function ajax_make_payments() {
        global $birchschedule;

        $appointment_id = $_POST['birs_appointment_id'];
        $client_id = $_POST['birs_client_id'];
        $appointment1on1_config = 
            array(
                'appointment1on1_keys' => array(
                    '_birs_appointment1on1_price'
                )
            );
        $appointment1on1 = 
            self::$birchschedule->model->booking->get_appointment1on1(
                $appointment_id, 
                $client_id,
                $appointment1on1_config
            );
        $appointment1on1['_birs_appointment1on1_price'] = $_POST['birs_appointment1on1_price'];
        self::$birchschedule->model->save($appointment1on1, $appointment1on1_config);
        $payments = array();
        if(isset($_POST['birs_appointment_payments'])) {
            $payments = $_POST['birs_appointment_payments'];
        }
        $config = array(
            'meta_keys' => self::$birchschedule->model->get_payment_fields(),
            'base_keys' => array()
        );        
		foreach($payments as $payment_trid => $payment) {
			$payment_info = $birchschedule->view->merge_request(array(), $config, $payment);
            $payment_info['_birs_payment_appointment'] = $appointment_id;
            $payment_info['_birs_payment_client'] = $client_id;
            $payment_info['_birs_payment_trid'] = $payment_trid;
            $payment_info['_birs_payment_currency'] = $birchschedule->model->get_currency_code();
            self::$birchschedule->model->booking->make_payment($payment_info);
		}

        self::$birchschedule->view->render_ajax_success_message(array(
            'code' => 'success',
            'message' => ''
        ));
	}

    static function ajax_render_payments() {
        $appointment_id = $_POST['birs_appointment_id'];
        $client_id = $_POST['birs_client_id'];
        echo self::$package->get_payments_details_html($appointment_id, $client_id);
        exit;
    }

    static function get_payments_details_html($appointment_id, $client_id) {
    	global $birchschedule, $birchpress;

        $price = 0;
        $payment_types = self::$package->get_payment_types();
        $payments = array();

        if ($appointment_id) {
            $appointment1on1 = $birchschedule->model->booking->get_appointment1on1(
                $appointment_id, 
                $client_id,
                array(
                    'appointment1on1_keys' => array(
                        '_birs_appointment1on1_price'
                ),
                'base_keys' => array()
            ));
            $price = $appointment1on1['_birs_appointment1on1_price'];
            $payments = 
                self::$birchschedule->model->booking->get_payments_by_appointment1on1($appointment_id, $client_id);
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
                    <input type="text" id="birs_appointment1on1_price" 
                        name="birs_appointment1on1_price"
                        value="<?php echo $price; ?>">
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Paid', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <span class="birs_money"
                        id="birs_appointment1on1_paid"></span>
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Due', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <span class="birs_money"
                        id="birs_appointment1on1_due"></span>
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
                    <input type="text" id="birs_appointment1on1_amount_to_pay" 
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
                <label>
                    &nbsp;
                </label>
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
        <input type="hidden" name="birs_client_id" id="birs_client_id" value="<?php echo $client_id; ?>" />
        <ul>
            <li class="birs_form_field">
                <label>
                    &nbsp;
                </label>
                <div class="birs_field_content">
                    <input name="birs_appointment_client_payments_save" 
                        id="birs_appointment_client_payments_save"
                        type="button" class="button-primary" 
                        value="<?php _e('Save', 'birchschedule'); ?>" />
                    <a href="javascript:void(0);" 
                        id="birs_appointment_client_payments_cancel"
                        style="padding: 4px 0 0 4px; display: inline-block;">
                        <?php _e('Cancel', 'birchschedule'); ?>
                    </a>
                </div>
            </li>
        </ul>
        <script type="text/javascript">
            (function($){
                $(birchschedule.view.appointments.edit.clientlist.payments.initPayments);
            })(jQuery);
        </script>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    static function ajax_add_new_payment() {
        global $birchpress, $birchschedule;

        $payment_types = self::$package->get_payment_types();
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
        <script type="text/javascript">
            (function($){
                $(birchschedule.view.appointments.edit.clientlist.payments.initNewPayment);
            })(jQuery);
        </script>
        <?php
        die();
    }
}

Birchschedule_View_Appointments_Edit_Clientlist_Payments_Imp::init_vars();
