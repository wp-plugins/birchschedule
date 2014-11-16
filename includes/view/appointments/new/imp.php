<?php

final class Birchschedule_View_Appointments_New_Imp {

    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->view->appointments->new;
    }

    static function init() {
        add_action('admin_init', array(self::$package, 'wp_admin_init'));
        add_action('init', array(self::$package, 'wp_init'));
        add_action('birchschedule_view_register_common_scripts_after', 
            array(self::$package, 'register_scripts'));
    }

    static function wp_init() {
        self::$birchschedule->view->register_script_data_fn(
            'birchschedule_view_appointments_new', 'birchschedule_view_appointments_new', 
            array(self::$package, 'get_script_data_fn_view_appointments_new'));
    }

    static function wp_admin_init() {
        add_action('wp_ajax_birchschedule_view_appointments_new_schedule', 
            array(self::$package, 'ajax_schedule'));
    }

    static function get_script_data_fn_view_appointments_new() {
        return array(
            'services_staff_map' => self::$package->get_services_staff_map(),
            'services_prices_map' => self::$package->get_services_prices_map(),
            'services_duration_map' => self::$package->get_services_duration_map(),
            'locations_map' => self::$package->get_locations_map(),
            'locations_staff_map' => self::$package->get_locations_staff_map(),
            'locations_services_map' => self::$package->get_locations_services_map(),
            'locations_order' => self::$package->get_locations_listing_order(),
            'staff_order' => self::$package->get_staff_listing_order(),
            'services_order' => self::$package->get_services_listing_order(),
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

    static function load_post_new_birs_appointment($arg) {
        add_action('add_meta_boxes', 
            array(self::$package, 'add_meta_boxes'));
    }

    static function register_scripts() {
        $version = self::$birchschedule->product_version;

        wp_register_script('birchschedule_view_appointments_new', 
            self::$birchschedule->plugin_url() . '/assets/js/view/appointments/new/base.js', 
            array('birchschedule_view_admincommon', 'birchschedule_view', 'jquery-ui-datepicker'), "$version");
    }

    static function enqueue_scripts_post_new_birs_appointment($arg) {
        self::$birchschedule->view->register_3rd_scripts();
        self::$birchschedule->view->register_3rd_styles();
        self::$birchschedule->view->enqueue_scripts(
            array(
                'birchschedule_view_appointments_new'
            )
        );
        self::$birchschedule->view->enqueue_styles(array('birchschedule_appointments_new'));
    }

    static function add_meta_boxes() {
        add_meta_box('meta_box_birs_appointment_new_booking', __('Appointment Info', 'birchschedule'), 
            array(self::$package, 'render_booking_info'), 'birs_appointment', 'normal', 'high');
        add_meta_box('meta_box_birs_appointment_new_actions', __('Actions', 'birchschedule'), 
            array(self::$package, 'render_actions'), 'birs_appointment', 'side', 'high');
    }

    static function get_time_options($time) {
        global $birchpress;

        $options = $birchpress->util->get_time_options(5);
        ob_start();
        $birchpress->util->render_html_options($options, $time);
        return ob_get_clean();
    }

    static function get_appointment_info_html() {
        global $birchpress;

        if(isset($_GET['apttimestamp'])) {
            $timestamp = $birchpress->util->get_wp_datetime($_GET['apttimestamp']);
            $date = $timestamp->format('m/d/Y');
            $time = $timestamp->format('H') * 60 + $timestamp->format('i');
        } else {
            $date = '';
            $time = 540;
        }
        $location_id = 0;
        $service_id = 0;
        $staff_id = 0;
        if(isset($_GET['locationid']) && $_GET['locationid'] != -1) {
            $location_id = $_GET['locationid'];
        } 
        if(isset($_GET['staffid']) && $_GET['staffid'] != -1) {
            $staff_id = $_GET['staffid'];
        } 
        ob_start();
        ?>
        <ul>
            <li class="birs_form_field birs_appointment_location">
                <label>
                    <?php _e('Location', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_location" name="birs_appointment_location" 
                        data-value="<?php echo $location_id; ?>">
                    </select>
                </div>
            </li>
            <li class="birs_form_field birs_appointment_service">
                <label>
                    <?php _e('Service', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_service" name="birs_appointment_service"
                        data-value="<?php echo $service_id; ?>">
                    </select>
                </div>
            </li>
            <li class="birs_form_field birs_appointment_staff"> 
                <label>
                    <?php _e('Provider', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_staff" name="birs_appointment_staff"
                        data-value="<?php echo $staff_id; ?>">
                    </select>
                </div>
                <div class="birs_error" id="birs_appointment_service_error"></div>
            </li>
            <li class="birs_form_field birs_appointment_date"> 
                <label>
                    <?php _e('Date', 'birchschedule'); ?>
                </label>
                <input id="birs_appointment_date" name="birs_appointment_date" type="hidden" value="<?php echo $date; ?>">
                <div  class="birs_field_content">
                    <div id="birs_appointment_datepicker">
                    </div>
                </div>
                <div class="birs_error" id="birs_appointment_date_error"></div>
            </li>
            <li class="birs_form_field birs_appointment_time">
                <label>
                    <?php _e('Time', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_time" name="birs_appointment_time" size='1'>
                        <?php echo self::$package->get_time_options($time); ?>
                    </select>
                </div>
                <div class="birs_error" id="birs_appointment_time_error"></div>
            </li>
        </ul>
        <?php        
        return ob_get_clean();
    }

    static function get_client_info_html() {
        return self::$birchschedule->view->appointments->edit->clientlist->edit->get_client_info_html(0);
    }

    static function get_appointment1on1_info_html() {
        return self::$birchschedule->view->appointments->edit->clientlist->edit->get_appointment1on1_info_html(0, 0);
    }

    static function render_client_info_header() {
        ?>
        <h3 class="birs_section"><?php _e('Client Info', 'birchschedule'); ?></h3>
        <?php
    }

    static function render_booking_info($post) {
        echo self::$package->get_appointment_info_html();
        ?>
        <input type="hidden" id="birs_appointment_duration" name="birs_appointment_duration" />
        <?php
            self::$package->render_client_info_header();
        ?>
        <div id="birs_client_info_container">
        <?php
            echo self::$package->get_client_info_html();
        ?>
        </div>
        <h3 class="birs_section"><?php _e('Additional Info', 'birchschedule'); ?></h3>
        <?php
            echo self::$package->get_appointment1on1_info_html();
        ?>
        <ul>
            <li class="birs_form_field birs_please_wait" style="display:none;">
                <label>
                    &nbsp;
                </label>
                <div class="birs_field_content">
                    <div><?php _e('Please wait...', 'birchschedule'); ?></div>
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    &nbsp;
                </label>
                <div class="birs_field_content">
                    <input type="button" id="birs_appointment_actions_schedule" class="button-primary" value="<?php _e('Schedule', 'birchschedule'); ?>" />
                </div>
            </li>
        </ul>
        <?php
    }

    static function render_actions() {
        $back_url = self::$birchschedule->view->appointments->edit->get_back_to_calendar_url();
        ?>
        <div class="submitbox">
            <div style="float:left;">
                <a href="<?php echo $back_url; ?>">
                    <?php _e('Back to Calendar', 'birchschedule'); ?>
                </a>
            </div>
            <div class="clear"></div>
        </div>
        <?php
    }

    static function validate_appointment_info() {
        global $birchpress;

        $errors = array();
        if (!isset($_POST['birs_appointment_staff']) || !isset($_POST['birs_appointment_service'])) {
            $errors['birs_appointment_service'] = __('Please select a service and a provider', 'birchschedule');
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
            $errors['birs_client_name_first'] = __('This field is required', 'birchschedule');
        }
        if (!$_POST['birs_client_name_last']) {
            $errors['birs_client_name_last'] = __('This field is required', 'birchschedule');
        }
        if (!$_POST['birs_client_email']) {
            $errors['birs_client_email'] = __('Email is required', 'birchschedule');
        } else if (!is_email($_POST['birs_client_email'])) {
            $errors['birs_client_email'] = __('Email is incorrect', 'birchschedule');
        }
        if (!$_POST['birs_client_phone']) {
            $errors['birs_client_phone'] = __('This field is required', 'birchschedule');
        }

        return $errors;
    }

    static function validate_appointment1on1_info() {
        return array();
    }

    static function ajax_schedule() {
        global $birchpress;

        $appointment_errors = self::$package->validate_appointment_info();
        $appointment1on1_errors = self::$package->validate_appointment1on1_info();
        $client_errors = self::$package->validate_client_info();
        $errors = array_merge($appointment_errors, $appointment1on1_errors, $client_errors);
        if($errors) {
            self::$birchschedule->view->render_ajax_error_messages($errors);
        }
        $client_config = array(
            'base_keys' => array(),
            'meta_keys' => $_POST['birs_client_fields']
        );
        $client_info = self::$birchschedule->view->merge_request(array(), $client_config, $_POST);
        unset($client_info['ID']);
        $client_id = self::$birchschedule->model->booking->save_client($client_info);
        $appointment1on1_config = array(
            'base_keys' => array(),
            'meta_keys' => array_merge(
                self::$birchschedule->model->get_appointment_fields(),
                self::$birchschedule->model->get_appointment1on1_fields(),
                self::$birchschedule->model->get_appointment1on1_custom_fields()
            )
        );
        $appointment1on1_info = 
            self::$birchschedule->view->merge_request(array(), $appointment1on1_config, $_POST);
        $datetime = array(
            'date' => $_POST['birs_appointment_date'],
            'time' => $_POST['birs_appointment_time']
        );
        $datetime = $birchpress->util->get_wp_datetime($datetime);
        $timestamp = $datetime->format('U');
        $appointment1on1_info['_birs_appointment_timestamp'] = $timestamp;
        $appointment1on1_info['_birs_client_id'] = $client_id;
        unset($appointment1on1_info['ID']);
        unset($appointment1on1_info['_birs_appointment_id']);
        $appointment1on1_id = self::$birchschedule->model->booking->make_appointment1on1($appointment1on1_info);
        self::$birchschedule->model->booking->change_appointment1on1_status($appointment1on1_id, 'publish');

        if($appointment1on1_id) {
            $cal_url = admin_url('admin.php?page=birchschedule_calendar');
            $refer_query = parse_url(wp_get_referer(), PHP_URL_QUERY);
            $hash_string = self::$birchschedule->view->get_query_string($refer_query, 
                array(
                    'calview', 'locationid', 'staffid', 'currentdate'
                )
            );
            if($hash_string) {
                $cal_url = $cal_url . '#' . $hash_string;
            }
            self::$birchschedule->view->render_ajax_success_message(array(
                'code' => 'success',
                'message' => json_encode(array(
                    'url' => htmlentities($cal_url)
                ))
            ));
        }
    }

}

Birchschedule_View_Appointments_New_Imp::init_vars();

?>