<?php

class BIRS_Shortcode {

    const SHORTCODE_NAME = 'bp-scheduler-bookingform';

    function __construct() {        
        add_action('init', array(&$this, 'init'));
        add_action('wp_ajax_nopriv_birs_save_appointment_frontend', array(&$this, 'ajax_save_appointment'));
        add_action('wp_ajax_birs_save_appointment_frontend', array(&$this, 'ajax_save_appointment'));
        add_action('wp_ajax_nopriv_birs_get_avaliable_time', array(&$this, 'ajax_get_avaliable_time'));
        add_action('wp_ajax_birs_get_avaliable_time', array(&$this, 'ajax_get_avaliable_time'));
        add_filter('birchschedule_ajax_booking_response', array($this, 'get_ajax_booking_response'), 10, 3);
        add_filter('birchschedule_save_appointment_frontend', array($this, 'save_appointment'));
        add_filter('birchschedule_service_option_text', 
            array($this, 'get_service_option_text'), 10, 2);
    }

    function init() {
        add_shortcode(self::SHORTCODE_NAME, array(&$this, 'get_shortcode_html'));
        add_filter('birchschedule_validate_booking_form_info', array($this, 'validate_booking_form'));
        add_filter('birchschedule_booking_form_fields', array($this, 'get_form_fields_html'));
        add_filter('birchschedule_staff_listing_order', array($this, 'get_staff_listing_order'));
        add_filter('birchschedule_service_listing_order', array($this, 'get_service_listing_order'));
    }

    function get_calendar_view() {
        global $birchschedule;
        return $birchschedule->calendar_view;
    }

    function add_js_css() {
        $calendar = $this->get_calendar_view();
        $params = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'all_schedule' => $this->get_all_schedule(),
            'all_dayoffs' => $this->get_all_dayoffs(),
            'service_price_map' => $calendar->get_service_price_map(),
            'service_duration_map' => $calendar->get_service_duration_map(),
            'service_staff_map' => $calendar->get_service_staff_map(),
            'location_staff_map' => apply_filters('birchschedule_location_staff_map', array()),
            'location_service_map' => apply_filters('birchschedule_location_service_map', array()),
            'staff_order' => apply_filters('birchschedule_staff_listing_order', array()),
            'service_order' => apply_filters('birchschedule_service_listing_order', array()),
            'gmt_offset' =>$this->get_util()->get_gmt_offset(),
            'datepicker_i18n_options' => $this->get_util()->get_datepicker_i18n_params(),
            'future_time' => $this->get_future_time(),
            'cut_off_time' => $this->get_cut_off_time()
        );
        wp_enqueue_script('birchschedule');
        wp_localize_script('birchschedule', 'birs_params', $params);

        wp_enqueue_style('birchschedule_styles');
    }

    function get_staff_listing_order($orders) {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_staff',
                            'order' => 'ASC',
                            'orderby' => 'title'
                        ),
                        array(
                            'base_keys' => array(
                                'post_title'
                            )
                        )
        );
        $staff = $query->query();
        return array_keys($staff);
    }

    function get_service_listing_order() {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_service',
                            'order' => 'ASC',
                            'orderby' => 'title'
                        ),
                        array(
                            'base_keys' => array(
                                'post_title'
                            )
                        )
        );
        $services = $query->query();
        return array_keys($services);
    }
    
    function get_cut_off_time() {
        return apply_filters('birchschedule_booking_preferences_cut_off_time', 1);
    }
    
    function get_future_time() {
        return apply_filters('birchschedule_booking_preferences_future_time', 360);
    }
    
    function get_ajax_booking_response($response, $appointment_id, $errors){
        ob_start();
        ?>
        <div id="birs_response">
            <?php
            if (!$errors):
                $appointment = new BIRS_Appointment($appointment_id, array(
                            'meta_keys' => array(
                                '_birs_appointment_location',
                                '_birs_appointment_service',
                                '_birs_appointment_staff',
                                '_birs_appointment_timestamp'
                            )
                        ));
                $appointment->load();
                $location = new BIRS_Location($appointment['_birs_appointment_location'], array(
                            'base_keys' => array(
                                'post_title'
                            )
                        ));
                $location->load();
                $service = new BIRS_Service($appointment['_birs_appointment_service'], array(
                            'base_keys' => array(
                                'post_title'
                            ),
                            'meta_keys' => array(
                                '_birs_service_length', '_birs_service_length_type',
                                '_birs_service_padding', '_birs_service_padding_type'
                            )
                        ));
                $service->load();
                $service_length = $service->get_service_length();
                $staff = new BIRS_Staff($appointment['_birs_appointment_staff'], array(
                            'base_keys' => array(
                                'post_title'
                            )
                        ));
                $staff->load();
                $time = $this->get_util()->convert_to_datetime($appointment['_birs_appointment_timestamp']);
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
                                    <p><?php echo " $service->post_title ($service_length mins) with $staff->post_title"; ?></p>
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
    
    function save_appointment() {
        return $this->get_calendar_view()->save_appointment();
    }

    function set_request_appointment_status() {
        $service_id = $_POST['birs_appointment_service'];
        $service = new BIRS_Service($service_id, array(
            'meta_keys' => array('_birs_service_pre_payment_fee', '_birs_service_price')
        ));
        $service->load();
        $pre_payment_fee = $service->get_pre_payment_fee();
        $is_pre_payment = apply_filters('birchschedule_is_pre_payment',
            false, $_POST['birs_appointment_service']);
        if($is_pre_payment && $pre_payment_fee >= 0.01) {
            $_POST['birs_appointment_status'] = 'pending';
        }
    }

    function ajax_save_appointment() {
        $permitted = check_ajax_referer("birs_save_appointment-0", '_wpnonce', false);
        $appointment_id = 0;
        if ($permitted) {
            $errors = apply_filters('birchschedule_validate_booking_form_info', array());
            if (!$errors) {
                $this->set_request_appointment_status();
                $appointment_id = apply_filters('birchschedule_save_appointment_frontend', 0);
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
        echo apply_filters('birchschedule_ajax_booking_response', '', $appointment_id, $errors);
        die;
    }

    function get_all_schedule() {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_staff'
                        ),
                        array(
                            'meta_keys' => array(
                                '_birs_staff_schedule'
                            )
                ));
        $staff = $query->query();
        $allschedule = array();
        foreach (array_values($staff) as $thestaff) {
            $schedule = $thestaff->get_all_calculated_schedule();
            $allschedule[$thestaff->ID] = $schedule;
        }
        return $allschedule;
    }
    
    function get_all_dayoffs() {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_staff'
                        ),
                        array(
                            'meta_keys' => array(
                                '_birs_staff_dayoffs'
                            )
                ));
        $staff = $query->query();
        $dayoffs = array();
        foreach (array_values($staff) as $thestaff) {
            $dayoffs[$thestaff->ID] = 
                apply_filters('birchschedule_staff_days_off', 
                    $thestaff->_birs_staff_dayoffs, $thestaff->ID);
        }
        return $dayoffs;
    }

    function get_util() {
        return BIRS_Util::get_instance();
    }

    function render_service_options($selection) {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_service',
                            'order' => 'ASC',
                            'orderby' => 'title'
                        ),
                        array(
                            'meta_keys' => array(
                                '_birs_service_length', '_birs_service_length_type',
                                '_birs_service_price', '_birs_service_price_type'
                            ),
                            'base_keys' => array(
                                'post_title'
                            )
                ));
        $services = $query->query();
        foreach ($services as $service_id => $service) {
            if ($service_id == $selection) {
                $selected = ' selected="selected" ';
            } else {
                $selected = '';
            }
            echo "<option value='$service->ID' $selected>" .
                apply_filters('birchschedule_service_option_text', 
                    "", $service) . 
                "</option>";

        };
        if ($selection) {
            return $selection;
        } else {
            return key($services);
        }
    }

    function get_service_option_text($text, $service) {
        $length = $service->get_service_length();
        $price = $this->get_service_price_text($service);
        $seperator = "-";
        if ($service['_birs_service_price_type'] == "dont-show") {
            $seperator = "";
        }
        return "$service->post_title ($length " . 
            __("mins", "birchschedule") . ") $seperator $price" ;
    }
    
    function validate_booking_time() {
        $errors = array();
        if (!isset($_POST['birs_appointment_time']) || !$_POST['birs_appointment_time']) {
            $errors['birs_appointment_time'] = __('Time is required', 'birchschedule');
            return $errors;
        }
        $avaliable_times = $this->get_avaliable_time();
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

    function validate_booking_form() {
        $calendar = $this->get_calendar_view();
        $errors = $calendar->validate_data();
        $time_error = $this->validate_booking_time();
        $errors = array_merge($errors, $time_error);
        return $errors;
    }

    function get_avaliable_time() {
        $location_id = 0;
        if (isset($_POST['birs_appointment_location'])) {
            $location_id = $_POST['birs_appointment_location'];
        }
        $service_id = 0;
        if (isset($_POST['birs_appointment_service'])) {
            $service_id = $_POST['birs_appointment_service'];
        }
        $staff_id = 0;
        if (isset($_POST['birs_appointment_staff'])) {
            $staff_id = $_POST['birs_appointment_staff'];
        }
        $date = 0;
        if (isset($_POST['birs_appointment_date'])) {
            $date = $_POST['birs_appointment_date'];
        }
        if (!($location_id && $service_id && $staff_id && $date)) {
            return array();
        }
        $staff = new BIRS_Staff($staff_id, array(
                    'meta_keys' => array(
                        '_birs_staff_schedule'
                    )
                ));
        $staff->load();
        $time_options = $staff->get_avaliable_time($location_id, $service_id, $date);
        return $time_options;
    }

    function ajax_get_avaliable_time() {
        ?>
        <div>
        <?php
        $time_options = $this->get_avaliable_time();
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

    public function get_service_price_text($service) {
        global $birchschedule;
        $services_view = $birchschedule->services_view;
        $text_map = $services_view->get_price_type_text_map();
        $price_type = $service['_birs_service_price_type'];
        if ($price_type == 'fixed') {
            return apply_filters('birchschedule_price', $service['_birs_service_price']);
        } else if ($price_type == 'dont-show') {
            return '';
        } else {
            return $text_map[$price_type];
        }
    }

    function get_shortcode_html($attr) {
        do_action('birchschedule_render_shortcode_booking_form');
        $this->add_js_css();
        $calendar = $this->get_calendar_view();
        ob_start();
        ?>
        <div class="birchschedule" id="birs_booking_box">
            <form id="birs_appointment_form">
                <input type="hidden" id="birs_appointment_price" name="birs_appointment_price">
                <input type="hidden" id="birs_appointment_duration" name="birs_appointment_duration">
                <input type="hidden" id="birs_shortcode_page_url" 
                    name="birs_shortcode_page_url"
                    value="<?php echo esc_attr($this->get_util()->current_page_url()); ?>">
                <div>
                    <?php wp_nonce_field("birs_save_appointment-0"); ?>
                    <?php echo apply_filters('birchschedule_booking_form_fields', ''); ?>
                </div>
            </form>
        </div>
        <div id="birs_booking_success">
        </div>
        <?php
        return ob_get_clean();
    }

    function get_form_fields_html($html) {
        $calendar = $this->get_calendar_view();
        $location_staff_map = apply_filters('birchschedule_location_staff_map', array());
        $hide_location_class = "";
        if(sizeof($location_staff_map) == 1) {
            $hide_location_class = "birs_hidden";
        }
        $service_staff_map = $calendar->get_service_staff_map();
        $hide_service_class = "";
        if(sizeof($service_staff_map) == 1) {
            $hide_service_class = "birs_hidden";
        }
        $staff_query = new BIRS_Model_Query(
            array(
                'post_type' => 'birs_staff'
            )
        );
        $staff = $staff_query->query();
        if(sizeof($staff) == 1) {
            $hide_staff_class = "birs_hidden";
        }
        ob_start();
        ?>
        <ul>
        <li class="birs_form_field">
            <h2 class="birs_section"><?php _e('Appointment Info', 'birchschedule'); ?></h2>
        </li>
        <li class="birs_form_field <?php echo $hide_location_css; ?>">
            <label><?php _e('Location', 'birchschedule'); ?></label>
            <div class="birs_field_content">
                <select id="birs_appointment_location" name="birs_appointment_location">
                    <?php $location_id = $calendar->render_location_options(); ?>
                </select>
            </div>
        </li>
        <li class="birs_form_field <?php echo $hide_service_css; ?>">
            <label><?php _e('Service', 'birchschedule'); ?></label>
            <div class="birs_field_content">
                <select id="birs_appointment_service" name="birs_appointment_service">
                    <?php $service_id = $this->render_service_options(0); ?>
                </select>
            </div>
        </li>
        <li class="birs_form_field <?php echo $hide_staff_css; ?>"> 
            <label><?php _e('Service Provider', 'birchschedule'); ?></label>
            <div class="birs_field_content">
                <select id="birs_appointment_staff" name="birs_appointment_staff">
                    <?php $calendar->render_staff_options($location_id, $service_id, 0); ?>
                </select>
            </div>
            <div class="birs_error" id="birs_appointment_service_error"></div>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('Date', 'birchschedule'); ?></label>
            <input id="birs_appointment_date" name="birs_appointment_date" type="hidden">
            <div  class="birs_field_content">
                <div id="birs_appointment_datepicker">
                </div>
            </div>
            <div class="birs_error" id="birs_appointment_date_error"></div>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('Time', 'birchschedule'); ?></label>
            <input id="birs_appointment_time" name="birs_appointment_time" type="hidden">
            <div class="birs_field_content">
                <div id="birs_appointment_timeoptions">
                </div>
            </div>
            <div class="birs_error" id="birs_appointment_time_error"></div>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('Notes', 'birchschedule') ?></label>
            <div class="birs_field_content birs_field_paragraph">
                <textarea id="birs_appointment_notes" name="birs_appointment_notes"></textarea>
                <input type="hidden" name="birs_appointment_fields[]" value="_birs_appointment_notes" />
            </div>
        </li>
        <li class="birs_form_field">
            <h2 class="birs_section"><?php _e('Your Info', 'birchschedule'); ?></h2>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('First Name', 'birchschedule') ?></label>
            <div class="birs_field_content">
                <input id="birs_client_name_first" name="birs_client_name_first" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_first" />
            </div>
            <div class="birs_error" id="birs_client_name_first_error"></div>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('Last Name', 'birchschedule') ?></label>
            <div class="birs_field_content">
                <input id="birs_client_name_last" name="birs_client_name_last" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_last" />
            </div>
            <div class="birs_error" id="birs_client_name_last_error"></div>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('Email', 'birchschedule') ?></label>
            <div class="birs_field_content">
                <input id="birs_client_email" name="birs_client_email" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_email" />
            </div>
            <div class="birs_error" id="birs_client_email_error"></div>
        </li>
        <li class="birs_form_field"> 
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