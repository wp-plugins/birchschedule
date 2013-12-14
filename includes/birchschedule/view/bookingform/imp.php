<?php

class Birchschedule_View_Bookingform_Imp {

    private static $SC_BOOKING_FORM_LEGACY = 'bp-scheduler-bookingform';

    private static $SC_BOOKING_FORM = 'bpscheduler_booking_form';
    
    private static $temp_data;

    private function __construct() {}

    static function init() {
        global $birchschedule;

        $package = $birchschedule->view->bookingform;
        $package->add_action('init', 'wp_init');
        $package->add_action('wp_loaded', 'wp_loaded');
        add_filter('birchschedule_view_get_shortcodes', array($package, 'add_shortcode'));
        self::$temp_data = array();
    }

    static function wp_init() {
        global $birchschedule, $birchpress;

        $package = $birchschedule->view->bookingform;
        add_shortcode(self::$SC_BOOKING_FORM, array(__CLASS__, 'get_shortcode_content'));
        add_shortcode(self::$SC_BOOKING_FORM_LEGACY, array(__CLASS__, 'get_shortcode_content'));
        add_filter('the_content', array(__CLASS__, 'replace_shortcode_id_with_html'), 1000);
        add_filter('widget_text', 'do_shortcode', 11);
        add_filter('widget_text', array(__CLASS__, 'replace_shortcode_id_with_html'), 1000);
        add_action('wp_ajax_nopriv_birchschedule_view_bookingform_save_appointment', 
            array($package, 'ajax_save_appointment'));
        add_action('wp_ajax_birchschedule_view_bookingform_save_appointment', 
            array($package, 'ajax_save_appointment'));
        add_action('wp_ajax_nopriv_birchschedule_view_bookingform_get_avaliable_time', 
            array($package, 'ajax_get_avaliable_time'));
        add_action('wp_ajax_birchschedule_view_bookingform_get_avaliable_time', 
            array($package, 'ajax_get_avaliable_time'));
    }

    static function wp_loaded() {
        global $birchschedule;
        
        $package = $birchschedule->view->bookingform;
        $params = array(
            'services_prices_map' => $package->get_services_prices_map(),
            'services_duration_map' => $package->get_services_duration_map(),
            'services_staff_map' => $package->get_services_staff_map(),
            'locations_map' => $package->get_locations_map(),
            'locations_staff_map' => $package->get_locations_staff_map(),
            'locations_services_map' => $package->get_locations_services_map(),
            'staff_order' => $package->get_staff_listing_order(),
            'services_order' => $package->get_services_listing_order(),
            'locations_order' => $package->get_locations_listing_order(),
        );
        $birchschedule->view->register_script_data('birchschedule_view_bookingform', 
            'birchschedule_view_bookingform', $params);
    }

    static function add_shortcode($shortcodes) {
        $shortcodes[] = self::$SC_BOOKING_FORM;
        return $shortcodes;
    }

    static function enqueue_scripts() {
        global $birchschedule, $birchpress;

        $package = $birchschedule->view->bookingform;
        wp_enqueue_script('birchschedule_view_bookingform');
        wp_enqueue_style('birchschedule_bookingform');
    }

    static function get_all_schedule() {
        global $birchschedule;

        return $birchschedule->model->get_all_calculated_schedule();
    }

    static function get_all_daysoff() {
        global $birchschedule;

        return $birchschedule->model->get_all_daysoff();
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

    static function get_booking_response($appointment_id, $errors){
        global $birchschedule, $birchpress;

        ob_start();
        ?>
        <div id="birs_response">
            <?php
            if (!$errors):
                $appointment = $birchschedule->model->get($appointment_id, array(
                            'meta_keys' => array(
                                '_birs_appointment_location',
                                '_birs_appointment_service',
                                '_birs_appointment_staff',
                                '_birs_appointment_timestamp'
                            ),
                            'base_keys' => array()
                        ));
                $location = $birchschedule->model->get($appointment['_birs_appointment_location'], array(
                            'base_keys' => array(
                                'post_title'
                            ),
                            'meta_keys' => array()
                        ));
                $service = $birchschedule->model->get($appointment['_birs_appointment_service'], array(
                            'base_keys' => array(
                                'post_title'
                            ),
                            'meta_keys' => array(
                                '_birs_service_length', '_birs_service_length_type',
                                '_birs_service_padding', '_birs_service_padding_type'
                            )
                        ));
                $service_length = $birchschedule->model->get_service_length($service['ID']);
                $staff = $birchschedule->model->get($appointment['_birs_appointment_staff'], array(
                            'base_keys' => array(
                                'post_title'
                            ),
                            'meta_keys' => array()
                        ));
                $time = $birchpress->util->convert_to_datetime($appointment['_birs_appointment_timestamp']);
                ?>
                <div id="birs_success">
                    <div id='birs_success_text'>
                        <h3> <?php _e('Your appointment has been booked successfully.', 'birchschedule'); ?></h3>
                        <div>
                            <ul>
                                <li>
                                    <h4><?php _e('Location:', 'birchschedule'); ?></h4>
                                    <p><?php echo $location['post_title']; ?></p>
                                </li>
                                <li>
                                    <h4><?php _e('Service:', 'birchschedule'); ?></h4>
                                    <p><?php echo " $service[post_title] ($service_length mins) with $staff[post_title]"; ?></p>
                                </li>
                                <li>
                                    <h4><?php _e('Time:', 'birchschedule'); ?></h4>
                                    <p><?php echo $time; ?></p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div id="birs_errors">
                    <?php foreach ($errors as $error_id => $message): ?>
                        <p id="<?php echo $error_id; ?>"><?php echo $message; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    static function validate_appointment_info() {
        global $birchpress;

        $errors = array();
        if (!isset($_POST['birs_appointment_staff']) || !isset($_POST['birs_appointment_service'])) {
            $errors['birs_appointment_service'] = __('Please select a service and a service provider', 'birchschedule');
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

    static function validate_booking_time() {
        global $birchschedule;

        $errors = array();
        if (!isset($_POST['birs_appointment_time']) || !$_POST['birs_appointment_time']) {
            $errors['birs_appointment_time'] = __('Time is required', 'birchschedule');
            return $errors;
        }
        $avaliable_times = $birchschedule->view->bookingform->get_avaliable_time();
        $time = $_POST['birs_appointment_time'];
        $valid = array_key_exists($time, $avaliable_times) && $avaliable_times[$time]['avaliable'];
        if (!$valid) {
            $errors = array_merge(
                    array(
                        'birs_appointment_time' => __('Time is unavaliable', 'birchschedule'
                    )), $errors);
        }
        return $errors;
    }

    static function validate_booking_info() {
        global $birchschedule;

        $appointment_errors = $birchschedule->view->bookingform->validate_appointment_info();
        $client_errors = $birchschedule->view->bookingform->validate_client_info();
        $time_errors = $birchschedule->view->bookingform->validate_booking_time();
        return array_merge($appointment_errors, $client_errors, $time_errors);
    }

    static function save_client() {
        global $birchschedule;

        return $birchschedule->view->bookingadmin->save_client();
    }

    static function extract_appointment_meta_keys() {
        global $birchschedule;

        return $birchschedule->view->bookingadmin->extract_appointment_meta_keys();
    }

    static function extract_appointment($fields) {
        global $birchschedule;

        return $birchschedule->view->bookingadmin->extract_appointment($fields);
    }
    
    static function save_appointment() {
        global $birchschedule, $birchpress;

        $fields = $birchschedule->view->bookingform->extract_appointment_meta_keys();
        $appointment = $birchschedule->view->bookingform->extract_appointment($fields);
        $is_pre_payment = 
            $birchschedule->model->is_prepayment_enabled($appointment['_birs_appointment_service']);
        $pre_payment_fee = 
            $birchschedule->model->get_service_pre_payment_fee($appointment['_birs_appointment_service']);
        if($is_pre_payment && $pre_payment_fee >= 0.01) {
            $appointment['post_status'] = 'pending';
        }
        $client_id = $birchschedule->view->bookingform->save_client();
        $appointment['_birs_appointment_client'] = $client_id;
        $fields = $birchschedule->view->bookingform->extract_appointment_meta_keys();
        $config = array(
            'meta_keys' => $fields,
            'base_keys' => array(
                'post_title',
                'post_status'
            )
        );
        $appointment_id = $birchschedule->model->save($appointment, $config);
        return $appointment_id;
    }

    static function get_avaliable_time() {
        global $birchschedule, $birchpress;

        $staff_id = $_POST['birs_appointment_staff'];
        $location_id = $_POST['birs_appointment_location'];
        $service_id = $_POST['birs_appointment_service'];
        $date_text = $_POST['birs_appointment_date'];
        $date = $birchpress->util->get_wp_datetime(
            array(
                'date' => $date_text,
                'time' => 0
            )
        );

        $time_options = $birchschedule->model->get_staff_avaliable_time($staff_id, $location_id, 
            $service_id, $date);
        return $time_options;
    }

    static function ajax_save_appointment() {
        global $birchschedule;

        $package = $birchschedule->view->bookingform;
        $permitted = check_ajax_referer("birs_save_appointment-0", '_wpnonce', false);
        $appointment_id = 0;
        if ($permitted) {
            $errors = $package->validate_booking_info();
            if (!$errors) {
                $appointment_id = $package->save_appointment();
                if (!$appointment_id) {
                    $errors['birs_booking'] = __('Booking appointment failed', 'birchschedule');
                }
            }
        } else {
            $errors = array(
                'birs_booking' => __('Please refresh the page and book again.', 'birchschedule')
            );
        }
        $appointment = array();
        echo $package->get_booking_response($appointment_id, $errors);
        die;
    }

    static function ajax_get_avaliable_time() {
        global $birchschedule;

        $package = $birchschedule->view->bookingform;
        $i18n_messages = $birchschedule->view->get_frontend_i18n_messages();
        ?>
        <div>
        <?php
        $time_options = $package->get_avaliable_time();
        $empty = true;
        foreach ($time_options as $key => $value) {
            if ($value['avaliable']) {
                $text = $value['text'];
                echo "<span><a data-time='$key' href='javascript:void(0)'>$text</a></span>";
                $empty = false;
            }
        }
        if($empty) {
            echo "<p>" . $i18n_messages['There are no available times.'] . "</p>";
        }
        ?>
        </div>
        <?php
        die();
    }

    static function replace_shortcode_id_with_html($content) {
        global $birchschedule;
        if(!isset(self::$temp_data['shortcodes'])) {
            return $content;
        }
        $shortcodes = self::$temp_data['shortcodes'];
        foreach($shortcodes as $uid => $html) {
            $content = str_replace($uid, $html, $content);
        }
        return $content;
    }

    static function explode_ids($ids) {
        if(!$ids) {
            return false;
        }
        $ids = explode(',', $ids);
        $new_ids = array();
        foreach($ids as $id) {
            $new_ids[] = intval($id);
        }
        return $new_ids;
    }

    static function get_shortcode_html($attr) {
        global $birchschedule, $birchpress;

        $a = $attr;
        $a['location_ids'] = self::explode_ids($a['location_ids']);
        $a['service_ids'] = self::explode_ids($a['service_ids']);
        $a['staff_ids'] = self::explode_ids($a['staff_ids']);
        $birchschedule->view->bookingform->enqueue_scripts();
        wp_localize_script('birchschedule_view_bookingform', 'birchschedule_view_bookingform_sc_attrs', $a);
        ob_start();
        ?><style type="text/css">
        <?php
            echo $birchschedule->view->get_custom_code_css(self::$SC_BOOKING_FORM);
        ?>
        </style>
        <div class="birchschedule" id="birs_booking_box">
            <form id="birs_appointment_form">
                <input type="hidden" id="birs_appointment_price" name="birs_appointment_price" />
                <input type="hidden" id="birs_appointment_duration" name="birs_appointment_duration" />
                <input type="hidden" id="birs_shortcode_page_url" name="birs_shortcode_page_url" value="<?php echo esc_attr($birchpress->util->current_page_url()); ?>" />
                <div>
                    <?php wp_nonce_field("birs_save_appointment-0"); ?>
                    <?php echo $birchschedule->view->bookingform->get_fields_html(); ?>
                </div>
            </form>
        </div>
        <div id="birs_booking_success">
        </div>
        <?php
        $html = ob_get_clean();
        $html = self::minify_html($html);
        return $html;
    }

    static function minify_html($buffer) {

        $search = array(
            '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
            '/[^\S ]+\</s',  // strip whitespaces before tags, except space
            '/(\s)+/s',       // shorten multiple whitespace sequences
            '/\>\s+\</s'
        );

        $replace = array(
            '>',
            '<',
            '\\1',
            '><'
        );

        $buffer = preg_replace($search, $replace, $buffer);

        return $buffer;
    }

    static function get_shortcode_uid($attr) {
        global $birchschedule;

        $uid = 'birchpress_shortcode_' . uniqid();
        $html = $birchschedule->view->bookingform->get_shortcode_html($attr);
        self::$temp_data['shortcodes'][$uid] = $html;

        return $uid;
    }

    static function get_shortcode_content($attr) {
        global $birchschedule;

        $a = shortcode_atts(array(
                'location_ids' => false,
                'service_ids' => false,
                'staff_ids' => false,
                'nowrap' => false,
                'date' => false
            ), $attr);
        if($a['nowrap'] == 'yes') {
            return self::get_shortcode_uid($a);
        } else {
            return $birchschedule->view->bookingform->get_shortcode_html($a);
        }
    }

    static function get_fields_labels() {
        return array(
            'location' => __('Location', 'birchschedule'),
            'service' => __('Service', 'birchschedule'),
            'service_provider' => __('Service Provider', 'birchschedule'),
            'date' => __('Date', 'birchschedule'),
            'time' => __('Time', 'birchschedule'),
            'appointment_notes' => __('Notes', 'birchschedule'),
            'client_name_first' => __('First Name', 'birchschedule'),
            'client_name_last' => __('Last Name', 'birchschedule'),
            'client_email' => __('Email', 'birchschedule'),
            'client_phone' => __('Phone', 'birchschedule')
        );
    }

    static function get_fields_html() {
        global $birchschedule;

        $labels = $birchschedule->view->bookingform->get_fields_labels();
        ob_start();
        ?>
        <ul>
        <li class="birs_form_field birs_appointment_section">
            <h2 class="birs_section"><?php _e('Appointment Info', 'birchschedule'); ?></h2>
        </li>
        <li class="birs_form_field birs_appointment_location">
            <label><?php echo $labels['location']; ?></label>
            <div class="birs_field_content">
                <select id="birs_appointment_location" name="birs_appointment_location">
                </select>
            </div>
        </li>
        <li class="birs_form_field birs_appointment_service">
            <label><?php echo $labels['service']; ?></label>
            <div class="birs_field_content">
                <select id="birs_appointment_service" name="birs_appointment_service">
                </select>
            </div>
        </li>
        <li class="birs_form_field birs_appointment_staff"> 
            <label><?php echo $labels['service_provider']; ?></label>
            <div class="birs_field_content">
                <select id="birs_appointment_staff" name="birs_appointment_staff">
                </select>
            </div>
            <div class="birs_error" id="birs_appointment_service_error"></div>
        </li>
        <li class="birs_form_field birs_appointment_date"> 
            <label><?php echo $labels['date']; ?></label>
            <input id="birs_appointment_date" name="birs_appointment_date" type="hidden">
            <div  class="birs_field_content">
                <div id="birs_appointment_datepicker">
                </div>
            </div>
            <div class="birs_error" id="birs_appointment_date_error"></div>
        </li>
        <li class="birs_form_field birs_appointment_time"> 
            <label><?php echo $labels['time']; ?></label>
            <input id="birs_appointment_time" name="birs_appointment_time" type="hidden">
            <div class="birs_field_content">
                <div id="birs_appointment_timeoptions">
                </div>
            </div>
            <div class="birs_error" id="birs_appointment_time_error"></div>
        </li>
        <li class="birs_form_field birs_appointment_notes"> 
            <label><?php echo $labels['appointment_notes']; ?></label>
            <div class="birs_field_content birs_field_paragraph">
                <textarea id="birs_appointment_notes" name="birs_appointment_notes"></textarea>
                <input type="hidden" name="birs_appointment_fields[]" value="_birs_appointment_notes" />
            </div>
        </li>
        <li class="birs_form_field birs_client_section">
            <h2 class="birs_section"><?php _e('Your Info', 'birchschedule'); ?></h2>
        </li>
        <li class="birs_form_field birs_client_name_first"> 
            <label><?php echo $labels['client_name_first']; ?></label>
            <div class="birs_field_content">
                <input id="birs_client_name_first" name="birs_client_name_first" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_first" />
            </div>
            <div class="birs_error" id="birs_client_name_first_error"></div>
        </li>
        <li class="birs_form_field birs_client_name_last"> 
            <label><?php echo $labels['client_name_last']; ?></label>
            <div class="birs_field_content">
                <input id="birs_client_name_last" name="birs_client_name_last" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_last" />
            </div>
            <div class="birs_error" id="birs_client_name_last_error"></div>
        </li>
        <li class="birs_form_field birs_client_email"> 
            <label><?php echo $labels['client_email']; ?></label>
            <div class="birs_field_content">
                <input id="birs_client_email" name="birs_client_email" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_email" />
            </div>
            <div class="birs_error" id="birs_client_email_error"></div>
        </li>
        <li class="birs_form_field birs_client_phone"> 
            <label><?php echo $labels['client_phone']; ?></label>
            <div class="birs_field_content">
                <input id="birs_client_phone" name="birs_client_phone" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_phone" />
            </div>
            <div class="birs_error" id="birs_client_phone_error"></div>
        </li>
        <li class="birs_footer"> 
            <div class="birs_error" id="birs_booking_error"></div>
            <div style="display:none;" id="birs_please_wait"><?php _e('Please wait...', 'birchschedule'); ?></div>
            <div class="birs_field_content">
                <input type="button" value="<?php _e('Submit', 'birchschedule'); ?>" class="button" id="birs_book_appointment">
            </div>
        </li>
        </ul>
        <?php
        $html = ob_get_clean();
        return $html;
    }
}