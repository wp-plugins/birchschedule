<?php

class Birchschedule_View_Bookingadmin_Imp {

    private function __construct() {
    }

    static function init() {
        global $birchschedule;
        
        $package = $birchschedule->view->bookingadmin;
        $package->add_action('admin_init', 'wp_admin_init');
    }

    static function wp_admin_init() {
        global $birchschedule;
        
        $package = $birchschedule->view->bookingadmin;
        $package->add_action('admin_enqueue_scripts', 'enqueue_scripts');
        $package->add_action('wp_ajax_birchschedule_view_bookingadmin_render_edit_form', 
            'ajax_render_edit_form');
        $package->add_action('wp_ajax_birchschedule_view_bookingadmin_save_appointment', 
            'ajax_save_appointment');
        $package->add_action('wp_ajax_birchschedule_view_bookingadmin_delete_appointment', 
            'ajax_delete_appointment');
        $params = array(
            'service_staff_map' => $package->get_services_staff_map(),
            'service_price_map' => $package->get_services_prices_map(),
            'service_duration_map' => $package->get_services_duration_map(),
            'location_map' => $package->get_locations_map(),
            'location_staff_map' => $package->get_locations_staff_map(),
            'location_service_map' => $package->get_locations_services_map(),
            'location_order' => $package->get_locations_listing_order(),
            'staff_order' => $package->get_staff_listing_order(),
            'service_order' => $package->get_services_listing_order(),
        );
        $birchschedule->view->register_script_data('birchschedule_view_bookingadmin', 
            'birchschedule_view_bookingadmin', $params);
    }

    static function enqueue_scripts($hook) {
        global $birchschedule, $birchpress;

        $package = $birchschedule->view->bookingadmin;
        if($birchschedule->view->get_page_hook('calendar') !== $hook) {
            return;
        }

        $birchschedule->view->enqueue_scripts('birchschedule_view_bookingadmin');
        $birchschedule->view->enqueue_styles(
            array(
                'jquery-ui-bootstrap', 'fullcalendar_birchpress', 
                'birchschedule_admincommon', 'birchschedule_calendar', 
                'select2', 'jgrowl'
            )
        );
    }

    static function get_locations_map() {
        global $birchschedule;

        return $birchschedule->model->get_locations_map();
    }

    static function get_locations_staff_map() {
        global $birchschedule;

        return $birchschedule->model->get_locations_staff_map();
    }

    static function get_locations_services_map() {
        global $birchschedule;

        return $birchschedule->model->get_locations_services_map();
    }

    static function get_services_staff_map() {
        global $birchschedule;

        return $birchschedule->model->get_services_staff_map();
    }

    static function get_locations_listing_order() {
        global $birchschedule;

        return $birchschedule->model->get_locations_listing_order();
    }

    static function get_staff_listing_order() {
        global $birchschedule;

        return $birchschedule->model->get_staff_listing_order();
    }

    static function get_services_listing_order() {
        global $birchschedule;

        return $birchschedule->model->get_services_listing_order();
    }

    static function get_services_prices_map() {
        global $birchschedule;

        return $birchschedule->model->get_services_prices_map();
    }

    static function get_services_duration_map() {
        global $birchschedule;

        return $birchschedule->model->get_services_duration_map();
    }

    static function get_client_details_html($client_id) {
        global $birchschedule, $birchpress;

        $client_titles = $birchpress->util->get_client_title_options();
        $states = $birchpress->util->get_us_states();
        $countries = $birchpress->util->get_countries();
        $client_title = '';
        $address1 = '';
        $address2 = '';
        $city = '';
        $state = '';
        $province = '';
        $zip = '';
        $country = false;
        $default_country = $birchschedule->model->get_default_country();
        if ($client_id) {
            $client_title = get_post_meta($client_id, '_birs_client_title', true);
            $address1 = get_post_meta($client_id, '_birs_client_address1', true);
            $address2 = get_post_meta($client_id, '_birs_client_address2', true);
            $city = get_post_meta($client_id, '_birs_client_city', true);
            $state = get_post_meta($client_id, '_birs_client_state', true);
            $province = get_post_meta($client_id, '_birs_client_province', true);
            $country = get_post_meta($client_id, '_birs_client_country', true);
            $zip = get_post_meta($client_id, '_birs_client_zip', true);
        }
        ob_start();
        ?>
        <ul>
            <li class="birs_form_field">
                <label><?php _e('Title', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <select name="birs_client_title" id="birs_client_title">
                        <?php $birchpress->util->render_html_options($client_titles, $client_title); ?>
                    </select>
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_title" />
                </div>
            </li>
            <li class="birs_form_field">
                <label><?php _e('Address', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" name="birs_client_address1" id="birs_client_address1" value="<?php echo $address1; ?>">
                    <input type="text" name="birs_client_address2" id="birs_client_address2" value="<?php echo $address2; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_address1" />
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_address2" />
                </div>
            </li>
            <li class="birs_form_field">
                <label><?php _e('City', 'birchschedule') ?></label>
                <div class="birs_field_content">
                    <input type="text" name="birs_client_city" id="birs_client_city" value="<?php echo $city; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_city" />
                </div>
            </li>
            <li class="birs_form_field">
                <label><?php _e('State/Province', 'birchschedule') ?></label>
                <div class="birs_field_content">
                    <select name="birs_client_state" id ="birs_client_state">
                        <?php $birchpress->util->render_html_options($states, $state); ?>
                    </select>
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_state" />
                    <input type="text" name="birs_client_province" id="birs_client_province" value="<?php echo esc_attr($province); ?>" style="display: none;">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_province" />
                </div>
            </li>
            <li class="birs_form_field">
                <label><?php _e('Country', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <select name="birs_client_country" id="birs_client_country">
                        <?php
                        $birchpress->util->render_html_options($countries, $country, $default_country);
                        ?>
                    </select>
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_country" />
                </div>
            </li>
            <li class="birs_form_field">
                <label><?php _e('Zip Code', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" name="birs_client_zip" id="birs_client_zip" value="<?php echo $zip; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_zip" />
                </div>
            </li>
        </ul>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    static function get_appointment_details_html($appointment_id) {
        $notes = '';
        if ($appointment_id) {
            $notes = get_post_meta($appointment_id, '_birs_appointment_notes', true);
        }
        ob_start();
        ?>
        <ul>
            <li class="birs_form_field">
                <label>
                    <?php _e('Notes', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <textarea id="birs_appointment_notes" name="birs_appointment_notes"><?php echo $notes; ?></textarea>
                    <input type="hidden" name="birs_appointment_fields[]" value="_birs_appointment_notes" />
                </div>
            </li>
        </ul>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    static function ajax_render_edit_form() {
        global $birchschedule;

        $location_id = 0;
        $staff_id = 0;
        $appointment_id = 0;
        $client_id = 0;
        if (isset($_GET['birs_appointment_id'])) {
            $appointment_id = $_GET['birs_appointment_id'];
            $client_id = get_post_meta($appointment_id, '_birs_appointment_client', true);
        }
        ?>
        <div id="birs_appointment_edit">
            <ul>
                <li><a href="#birs_appointment_info"><?php _e('General Info', 'birchschedule'); ?></a></li>
                <li><a href="#birs_appointment_details"><?php _e('Appointment Details', 'birchschedule'); ?></a></li>
                <li><a href="#birs_client_details"><?php _e('Client Details', 'birchschedule'); ?></a></li>
                <li><a href="#birs_appointment_payments_details"><?php _e('Payments', 'birchschedule'); ?></a></li>
            </ul>
            <form id="birs_appointment_form">
                <div class="wrap" id="birs_appointment_info">
                    <?php wp_nonce_field("birs_save_appointment-$appointment_id"); ?>
                    <?php wp_nonce_field("birs_delete_appointment-$appointment_id", 'birs_delete_appointment_nonce', false); ?>
                    <input type="hidden" name="birs_appointment_id" id="birs_appointment_id" value="<?php echo $appointment_id; ?>">
                    <div id="birs_general_section_appointment">
                        <?php
                            echo $birchschedule->view->bookingadmin->get_appointment_general_html($appointment_id);
                        ?>
                    </div>
                    <div class="splitter"></div>
                    <div id="birs_general_section_client">
                        <?php echo $birchschedule->view->bookingadmin->get_client_general_html($client_id);
                        ?>
                    </div>
                </div>
                <div id="birs_appointment_details">
                    <?php
                    echo $birchschedule->view->bookingadmin->get_appointment_details_html($appointment_id);
                    ?>
                </div>
                <div id="birs_client_details">
                    <?php
                    echo $birchschedule->view->bookingadmin->get_client_details_html($client_id);
                    ?>
                </div>
                <div id="birs_appointment_payments_details">
                    <?php
                    echo $birchschedule->view->payments->get_payments_details_html($appointment_id);
                    ?>
                </div>
            </form>
        </div>
        <?php
        exit();
    }
    
    static function get_appointment_duration_html($appointment_duration) {
        ob_start();
        ?>
        <input type="hidden" id="birs_appointment_duration"
                name="birs_appointment_duration"
                value="<?php echo $appointment_duration; ?>" />
        <?php
        return ob_get_clean();
    }
    
    static function get_appointment_general_html($appointment_id) {
        global $birchschedule, $birchpress;

        $timestamp = time();
        $price = 0;
        $service_id = 0;
        $date = '';
        $date4picker='';
        $time = 540;
        $appointment_duration = 0;
        $location_id = 0;
        $staff_id = 0;
        if($appointment_id) {
            $timestamp = get_post_meta($appointment_id, '_birs_appointment_timestamp', true);
            $timestamp = $birchpress->util->get_wp_datetime($timestamp);
            $date4picker = $timestamp->format(get_option('date_format'));
            $date = $timestamp->format('m/d/Y');
            $time = $timestamp->format('H') * 60 + $timestamp->format('i');
    
            $appointment_duration = get_post_meta($appointment_id, '_birs_appointment_duration', true);
            $service_id = get_post_meta($appointment_id, '_birs_appointment_service', true);
            $staff_id = get_post_meta($appointment_id, '_birs_appointment_staff', true);
            $location_id = get_post_meta($appointment_id, '_birs_appointment_location', true);
        }
        ob_start();
        ?>
        <ul>
            <li class="birs_form_field">
                <label>
                    <?php _e('Location', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_location" name="birs_appointment_location"
                        style="width:0;" data-value="<?php echo $location_id; ?>">
                    </select>
                    <div class="birs_error" id="birs_appointment_location_error"></div>
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Service', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_service" name="birs_appointment_service"
                        style="width:0;" data-value="<?php echo $service_id; ?>">
                    </select>
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Staff', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_staff" name="birs_appointment_staff"
                        style="width:0;" data-value="<?php echo $staff_id; ?>">
                    </select>
                    <div class="birs_error" id="birs_appointment_service_error"></div>
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Date', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <input id="birs_appointment_datepicker" name="birs_appointment_datepicker" type="text" value="<?php echo $date4picker ?>">
                    <input id="birs_appointment_date" name="birs_appointment_date" type="hidden" value="<?php echo $date ?>">
                    <div class="birs_error" id="birs_appointment_date_error"></div>
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Time', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_time" name="birs_appointment_time"
                        style="width:0;">
                        <?php self::render_time_options($time); ?>
                    </select>
                    <div class="birs_error" id="birs_appointment_time_error"></div>
                </div>
            </li>
            <?php echo $birchschedule->view->bookingadmin->get_appointment_duration_html($appointment_duration); ?>
        </ul>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    static function get_client_general_html($client_id) {
        $first_name = '';
        $last_name = '';
        $email = '';
        $phone = '';
        if ($client_id) {
            $first_name = get_post_meta($client_id, '_birs_client_name_first', true);
            $last_name = get_post_meta($client_id, '_birs_client_name_last', true);
            $email = get_post_meta($client_id, '_birs_client_email', true);
            $phone = get_post_meta($client_id, '_birs_client_phone', true);
        }
        ob_start();
        ?>
        <ul>
            <li class="birs_form_field">
                <label><?php _e('First Name', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" id="birs_client_name_first" name="birs_client_name_first" value="<?php echo $first_name; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_first" />
                    <div class="birs_error" id="birs_client_name_first_error"></div>
                </div>
            </li>
            <li class="birs_form_field">
                <label><?php _e('Last Name', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" id="birs_client_name_last" name="birs_client_name_last" value="<?php echo $last_name; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_last" />
                    <div class="birs_error" id="birs_client_name_last_error"></div>
                </div>
            </li>
            <li class="birs_form_field">
                <label><?php _e('Email', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" id="birs_client_email" name="birs_client_email" value="<?php echo $email; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_email" />
                    <div class="birs_error" id="birs_client_email_error"></div>
                </div>
            </li>
            <li class="birs_form_field">
                <label><?php _e('Phone', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" id="birs_client_phone" name="birs_client_phone" value="<?php echo $phone; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_phone" />
                    <div class="birs_error" id="birs_client_phone_error"></div>
                </div>
            </li>
        </ul>
        <?php
        $html = ob_get_clean();
        return $html;
    }

    static function validate_appointment_info() {
        global $birchpress;

        $errors = array();
        if (!isset($_POST['birs_appointment_staff']) || !isset($_POST['birs_appointment_service'])) {
            $errors['birs_appointment_service'] = __('Please select a service and a staff', 'birchschedule');
        }
        if (!isset($_POST['birs_appointment_date']) || !$_POST['birs_appointment_date']) {
            $errors['birs_appointment_date'] = __('Date is required', 'birchschedule');
        }
        if (!isset($_POST['birs_appointment_time']) || !$_POST['birs_appointment_time']) {
            $errors['birs_appointment_time'] = __('Time is required', 'birchschedule');
        }
        if (isset($_POST['birs_appointment_date']) && $_POST['birs_appointment_date'] &&
            isset($_POST['birs_appointment_time']) && $_POST['birs_appointment_time']) {
            $datetime = array(
                'date' => $_POST['birs_appointment_date'],
                'time' => $_POST['birs_appointment_time']
            );
            $datetime = $birchpress->util->get_wp_datetime($datetime);
            if (!$datetime) {
                $errors['birs_appointment_datetime'] = __('Date & time is incorrect', 'birchschedule');
            }
        }
        return $errors;
    }

    static function validate_client_info() {
        $errors = array();
        if (!$_POST['birs_client_name_first']) {
            $errors['birs_client_name_first'] = __('First name is required', 'birchschedule');
        }
        if (!$_POST['birs_client_name_last']) {
            $errors['birs_client_name_last'] = __('Last name is required', 'birchschedule');
        }
        if (!$_POST['birs_client_email']) {
            $errors['birs_client_email'] = __('Email is required', 'birchschedule');
        } else if (!is_email($_POST['birs_client_email'])) {
            $errors['birs_client_email'] = __('Email is incorrect', 'birchschedule');
        }
        if (!$_POST['birs_client_phone']) {
            $errors['birs_client_phone'] = __('Phone is required', 'birchschedule');
        }

        return $errors;
    }

    static function validate_booking_info() {
        global $birchschedule;

        $appointment_errors = $birchschedule->view->bookingadmin->validate_appointment_info();
        $client_errors = $birchschedule->view->bookingadmin->validate_client_info();
        return array_merge($appointment_errors, $client_errors);
    }

    static function extract_appointment_meta_keys() {
        global $birchschedule;
        
        if (isset($_POST['birs_appointment_fields'])) {
            $fields = $_POST['birs_appointment_fields'];
        } else {
            $fields = array();
        }
        $appointment_meta_keys = $birchschedule->model->get_appointment_meta_keys();
        $fields = array_unique(array_merge($fields, $appointment_meta_keys));
        return $fields;
    }

    static function extract_appointment($fields) {
        global $birchschedule, $birchpress;

        if (isset($_POST['birs_appointment_id'])) {
            $appointment_id = $_POST['birs_appointment_id'];
        } else {
            $appointment_id = 0;
        }
        $config = array(
            'meta_keys' => $fields,
            'base_keys' => array(
                'post_title'
            )
        );
        $appointment = $birchschedule->view->merge_request(array(), $config);
        $appointment['_birs_appointment_padding_before'] = 
            $birchschedule->model->get_service_padding_before($appointment['_birs_appointment_service']);
        $appointment['_birs_appointment_padding_after'] = 
            $birchschedule->model->get_service_padding_after($appointment['_birs_appointment_service']);
        $datetime = array(
            'date' => $_POST['birs_appointment_date'],
            'time' => $_POST['birs_appointment_time']
        );
        $datetime = $birchpress->util->get_wp_datetime($datetime);
        $timestamp = $datetime->format('U');
        $appointment['_birs_appointment_timestamp'] = $timestamp;
        $appointment['ID'] = $appointment_id;
        $appointment['post_type'] = 'birs_appointment';

        return $appointment;
    }

    static function save_appointment() {
        global $birchschedule, $birchpress;

        $fields = $birchschedule->view->bookingadmin->extract_appointment_meta_keys();
        $appointment = $birchschedule->view->bookingadmin->extract_appointment($fields);
        $client_id = $birchschedule->view->bookingadmin->save_client();
        $appointment['_birs_appointment_client'] = $client_id;
        $config = array(
            'meta_keys' => $fields,
            'base_keys' => array(
                'post_title'
            )
        );
        $appointment_id = $birchschedule->model->save($appointment, $config);
        $birchschedule->view->payments->save_payments($appointment_id, $client_id);
        return $appointment_id;
    }

    static function save_client() {
        global $birchschedule;

        if (isset($_POST['birs_client_fields'])) {
            $fields = $_POST['birs_client_fields'];
        } else {
            $fields = array();
        }
        $config = array(
            'meta_keys' => $fields,
            'base_keys' => array(
                'post_title'
            )
        );
        $email = $_REQUEST['birs_client_email'];
        $client = $birchschedule->model->get_client_by_email($email, $config);
        if(!$client) {
            $client = array();
        }
        $client = $birchschedule->view->merge_request($client, $config);
        $client['post_type'] = 'birs_client';
        $client_id = $birchschedule->model->save($client, $config);
        return $client_id;
    }

    static function ajax_save_appointment() {
        global $birchschedule;

        if (isset($_POST['birs_appointment_id'])) {
            $appointment_id = $_POST['birs_appointment_id'];
        } else {
            $appointment_id = 0;
        }
        $permitted = check_ajax_referer("birs_save_appointment-$appointment_id", '_wpnonce', false);
        if($permitted) {
            $errors = $birchschedule->view->bookingadmin->validate_booking_info();
            if (!$errors) {
                $birchschedule->view->bookingadmin->save_appointment();
            }
        }
        ?>
        <div id="birs_response">
            <?php if ($errors): ?>
                <div id="birs_errors">
                    <?php foreach ($errors as $error_id => $message): ?>
                        <p id="<?php echo $error_id; ?>"><?php echo $message; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        die;
    }

    static function ajax_delete_appointment() {
        global $birchschedule;

        $appointment_id = $_REQUEST['birs_appointment_id'];
        check_ajax_referer("birs_delete_appointment-$appointment_id");
        $birchschedule->model->cancel_appointment($appointment_id);
        die;
    }
    
    static function render_time_options($selection) {
        global $birchpress;

        $options = $birchpress->util->get_time_options(5);
        foreach ($options as $val => $text) {
            if ($selection == $val) {
                $selected = ' selected="selected" ';
            } else {
                $selected = '';
            }
            echo "<option value='$val' $selected>$text</option>";
        }
    }
}
?>