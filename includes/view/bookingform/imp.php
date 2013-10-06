<?php

class Birchschedule_View_Bookingform_Imp {

    const SHORTCODE_NAME = 'bp-scheduler-bookingform';
    
    private static $temp_data;

    private function __construct() {}

    static function init() {
        global $birchschedule;

        $package = $birchschedule->view->bookingform;
        $package->add_action('init', 'wp_init');
        self::$temp_data = array();
    }

    static function wp_init() {
        global $birchschedule, $birchpress;

        $package = $birchschedule->view->bookingform;
        add_shortcode(self::SHORTCODE_NAME, array(__CLASS__, 'get_shortcode_content'));
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

    static function enqueue_scripts() {
        global $birchschedule, $birchpress;

        $package = $birchschedule->view->bookingform;
        $params = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'all_schedule' => $package->get_all_schedule(),
            'all_daysoff' => $package->get_all_daysoff(),
            'service_price_map' => $package->get_services_prices_map(),
            'service_duration_map' => $package->get_services_duration_map(),
            'service_staff_map' => $package->get_services_staff_map(),
            'location_map' => $package->get_locations_map(),
            'location_staff_map' => $package->get_locations_staff_map(),
            'location_service_map' => $package->get_locations_services_map(),
            'staff_order' => $package->get_staff_listing_order(),
            'service_order' => $package->get_services_listing_order(),
            'location_order' => $package->get_locations_listing_order(),
            'gmt_offset' => $birchpress->util->get_gmt_offset(),
            'datepicker_i18n_options' => $birchpress->util->get_datepicker_i18n_params(),
            'future_time' => $birchschedule->model->get_future_time(),
            'cut_off_time' => $birchschedule->model->get_cut_off_time()
        );
        wp_enqueue_script('birchschedule');
        wp_localize_script('birchschedule', 'birs_params', $params);

        wp_enqueue_style('birchschedule_styles');
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

    static function extract_appointment() {
        global $birchschedule;

        return $birchschedule->view->bookingadmin->extract_appointment();
    }
    
    static function save_appointment() {
        global $birchschedule, $birchpress;

        $appointment = $birchschedule->view->bookingform->extract_appointment();
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
                'post_title'
            )
        );
        $appointment_id = $birchschedule->model->save($appointment, $config);
        $birchschedule->view->payments->save_payments($appointment_id, $client_id);
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
            echo "<p>" . __('There are no available times.', 'birchschedule') . "</p>";
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
        ob_start();
        ?><style type="text/css">
        <?php
            echo $birchschedule->view->get_custom_code_css(self::SHORTCODE_NAME);
        ?>
        </style>
        <script type="text/javascript">
            var birs_params_shortcode_attrs = <?php echo json_encode($a); ?>;
        </script>
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
                'nowrap' => false
            ), $attr);
        if($a['nowrap'] == 'yes') {
            return self::get_shortcode_uid($a);
        } else {
            return $birchschedule->view->bookingform->get_shortcode_html($a);
        }
    }

    static function get_fields_html() {
        ob_start();
        ?>
        <ul>
        <li class="birs_form_field birs_appointment_section">
            <h2 class="birs_section"><?php _e('Appointment Info', 'birchschedule'); ?></h2>
        </li>
        <li class="birs_form_field birs_appointment_location">
            <label><?php _e('Location', 'birchschedule'); ?></label>
            <div class="birs_field_content">
                <select id="birs_appointment_location" name="birs_appointment_location">
                </select>
            </div>
        </li>
        <li class="birs_form_field birs_appointment_service">
            <label><?php _e('Service', 'birchschedule'); ?></label>
            <div class="birs_field_content">
                <select id="birs_appointment_service" name="birs_appointment_service">
                </select>
            </div>
        </li>
        <li class="birs_form_field birs_appointment_staff"> 
            <label><?php _e('Service Provider', 'birchschedule'); ?></label>
            <div class="birs_field_content">
                <select id="birs_appointment_staff" name="birs_appointment_staff">
                </select>
            </div>
            <div class="birs_error" id="birs_appointment_service_error"></div>
        </li>
        <li class="birs_form_field birs_appointment_date"> 
            <label><?php _e('Date', 'birchschedule'); ?></label>
            <input id="birs_appointment_date" name="birs_appointment_date" type="hidden">
            <div  class="birs_field_content">
                <div id="birs_appointment_datepicker">
                </div>
            </div>
            <div class="birs_error" id="birs_appointment_date_error"></div>
        </li>
        <li class="birs_form_field birs_appointment_time"> 
            <label><?php _e('Time', 'birchschedule'); ?></label>
            <input id="birs_appointment_time" name="birs_appointment_time" type="hidden">
            <div class="birs_field_content">
                <div id="birs_appointment_timeoptions">
                </div>
            </div>
            <div class="birs_error" id="birs_appointment_time_error"></div>
        </li>
        <li class="birs_form_field birs_appointment_notes"> 
            <label><?php _e('Notes', 'birchschedule') ?></label>
            <div class="birs_field_content birs_field_paragraph">
                <textarea id="birs_appointment_notes" name="birs_appointment_notes"></textarea>
                <input type="hidden" name="birs_appointment_fields[]" value="_birs_appointment_notes" />
            </div>
        </li>
        <li class="birs_form_field birs_client_section">
            <h2 class="birs_section"><?php _e('Your Info', 'birchschedule'); ?></h2>
        </li>
        <li class="birs_form_field birs_client_name_first"> 
            <label><?php _e('First Name', 'birchschedule') ?></label>
            <div class="birs_field_content">
                <input id="birs_client_name_first" name="birs_client_name_first" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_first" />
            </div>
            <div class="birs_error" id="birs_client_name_first_error"></div>
        </li>
        <li class="birs_form_field birs_client_name_last"> 
            <label><?php _e('Last Name', 'birchschedule') ?></label>
            <div class="birs_field_content">
                <input id="birs_client_name_last" name="birs_client_name_last" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_last" />
            </div>
            <div class="birs_error" id="birs_client_name_last_error"></div>
        </li>
        <li class="birs_form_field birs_client_email"> 
            <label><?php _e('Email', 'birchschedule') ?></label>
            <div class="birs_field_content">
                <input id="birs_client_email" name="birs_client_email" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_email" />
            </div>
            <div class="birs_error" id="birs_client_email_error"></div>
        </li>
        <li class="birs_form_field birs_client_phone"> 
            <label><?php _e('Phone', 'birchschedule') ?></label>
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