<?php

final class Birchschedule_View_Appointments_Edit_Imp {

    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->view->appointments->edit;
    }

    static function init() {
        add_action('admin_init', array(self::$package, 'wp_admin_init'));
        add_action('init', array(self::$package, 'wp_init'));
        add_action('birchschedule_view_register_common_scripts_after', 
            array(self::$package, 'register_scripts'));
    }

    static function wp_init() {
        self::$birchschedule->view->register_script_data_fn(
            'birchschedule_view_appointments_edit', 'birchschedule_view_appointments_edit', 
            array(self::$package, 'get_script_data_fn_view_appointments_edit'));
    }

    static function wp_admin_init() {
        add_action('wp_ajax_birchschedule_view_appointments_edit_reschedule', 
            array(self::$package, 'ajax_reschedule'));
        add_action('wp_ajax_birchschedule_view_appointments_edit_cancel', 
            array(self::$package, 'ajax_cancel'));
    }

    static function get_script_data_fn_view_appointments_edit() {
        return array(
            'services_staff_map' => self::$package->get_services_staff_map(),
            'locations_map' => self::$package->get_locations_map(),
            'services_map' => self::$package->get_services_map(),
            'locations_staff_map' => self::$package->get_locations_staff_map(),
            'locations_services_map' => self::$package->get_locations_services_map(),
            'locations_order' => self::$package->get_locations_listing_order(),
            'staff_order' => self::$package->get_staff_listing_order(),
            'services_order' => self::$package->get_services_listing_order(),
        );
    }

    static function register_scripts() {
        $version = self::$birchschedule->product_version;

        wp_register_script('birchschedule_view_appointments_edit', 
            self::$birchschedule->plugin_url() . '/assets/js/view/appointments/edit/base.js', 
            array('birchschedule_view_admincommon', 'birchschedule_view', 'jquery-ui-datepicker'), "$version");

    }

    static function get_locations_map() {
        global $birchschedule;

        return $birchschedule->model->get_locations_map();
    }

    static function get_services_map() {
        global $birchschedule;

        return $birchschedule->model->get_services_map();
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

    static function get_services_locations_map() {
        global $birchschedule;

        return $birchschedule->model->get_services_locations_map();
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

    static function load_post_edit_birs_appointment($arg) {
        birch_assert(is_array($arg) && isset($arg['post_type']) && 
            $arg['post_type'] == 'birs_appointment');
        
        add_action('add_meta_boxes', 
            array(self::$package, 'add_meta_boxes'));
    }

    static function enqueue_scripts_post_edit_birs_appointment($arg) {
        birch_assert(is_array($arg) && isset($arg['post_type']) && 
            $arg['post_type'] == 'birs_appointment');

        global $birchschedule;

        $birchschedule->view->register_3rd_scripts();
        $birchschedule->view->register_3rd_styles();
        $birchschedule->view->enqueue_scripts(
            array(
                'birchschedule_view_appointments_edit'
            )
        );
        $birchschedule->view->enqueue_styles(array('birchschedule_appointments_edit'));
    }

    static function validate_reschedule_info() {
        global $birchpress;

        $errors = array();
        if (!isset($_POST['birs_appointment_staff'])) {
            $errors['birs_appointment_staff'] = __('Please select a provider', 'birchschedule');
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

    static function ajax_reschedule() {
        global $birchpress;
        
        $errors = self::$package->validate_reschedule_info();
        if($errors) {
            self::$birchschedule->view->render_ajax_error_messages($errors);
        }
        $appointment_info = array();
        $datetime = array(
            'date' => $_POST['birs_appointment_date'],
            'time' => $_POST['birs_appointment_time']
        );
        $datetime = $birchpress->util->get_wp_datetime($datetime);
        $timestamp = $datetime->format('U');
        $appointment_info['_birs_appointment_timestamp'] = $timestamp;
        $appointment_info['_birs_appointment_staff'] = $_POST['birs_appointment_staff'];
        $appointment_info['_birs_appointment_location'] = $_POST['birs_appointment_location'];
        $appointment_info['_birs_appointment_service'] = $_POST['birs_appointment_service'];
        $appointment_id = $_POST['birs_appointment_id'];
        self::$birchschedule->model->booking->reschedule_appointment($appointment_id, $appointment_info);
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

    static function ajax_cancel() {
        $appointment_id = $_POST['birs_appointment_id'];
        self::$birchschedule->model->booking->cancel_appointment($appointment_id);
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
        $success = array(
            'code' => 'redirect_to_calendar',
            'message' => json_encode(array(
                'url' => htmlentities($cal_url)
            ))
        );
        self::$birchschedule->view->render_ajax_success_message($success);
    }

    static function add_meta_boxes() {
        add_meta_box('meta_box_birs_appointment_edit_info', __('Appointment Info', 'birchschedule'), 
            array(self::$package, 'render_appointment_info'), 'birs_appointment', 'normal', 'high');
        add_meta_box('meta_box_birs_appointment_edit_actions', __('Actions', 'birchschedule'), 
            array(self::$package, 'render_actions'), 'birs_appointment', 'side', 'high');
    }

    static function get_back_to_calendar_url() {
        $back_url = admin_url('admin.php?page=birchschedule_calendar');
        $hash_string = self::$birchschedule->view->get_query_string($_GET, 
            array(
                'calview', 'locationid', 'staffid', 'currentdate'
            )
        );
        if($hash_string) {
            $back_url = $back_url . '#' . $hash_string;
        }
        return $back_url;
    }

    static function render_actions($post) {
        $back_url = self::$package->get_back_to_calendar_url();
        ?>
        <div class="submitbox">
            <div style="float:left;">
                <a href="<?php echo $back_url; ?>">
                    <?php _e('Back to Calendar', 'birchschedule'); ?>
                </a>
            </div>
            <?php echo self::$package->get_action_cancel_html(); ?>
            <div class="clear"></div>
        </div>
        <?php

    }

    static function get_action_cancel_html() {
        ob_start()
        ?>
        <div style="float: right;">
            <a href="javascript:void(0);" class="submitdelete" id="birs_appointment_actions_cancel">
                <?php _e('Cancel Appointment', 'birchschedule'); ?>
            </a>
        </div>
        <?php        
        return ob_get_clean();
    }

    static function render_appointment_info($post) {
        global $birchschedule;

        $location_id = 0;
        $staff_id = 0;
        $appointment_id = 0;
        $client_id = 0;
        $appointment_id = $post->ID;
        $back_url = self::$package->get_back_to_calendar_url();
        ?>
        <div id="birs_appointment_info">
            <?php
                echo self::$package->get_appointment_info_html($appointment_id);
            ?>
            <ul>
                <li class="birs_form_field">
                    <label>
                        &nbsp;
                    </label>
                    <div class="birs_field_content">
                        <input type="button" class="button-primary" 
                            id="birs_appointment_actions_reschedule"
                            name="birs_appointment_actions_reschedule"
                            value="<?php _e('Reschedule', 'birchschedule'); ?>" />
                    </div>
                </li>
            </ul>
        </div>
        <?php
    }

    static function get_appointment_info_html($appointment_id) {
        global $birchpress;

        $appointment = self::$birchschedule->model->get($appointment_id, array(
            'base_keys' => array(),
            'meta_keys' => self::$birchschedule->model->get_appointment_fields()
        ));

        $options = $birchpress->util->get_time_options(5);
        if($appointment) {
            $location_id = $appointment['_birs_appointment_location'];
            $service_id = $appointment['_birs_appointment_service'];
            $staff_id = $appointment['_birs_appointment_staff'];
            $timestamp = $birchpress->util->get_wp_datetime($appointment['_birs_appointment_timestamp']);
            $date4picker = $timestamp->format(get_option('date_format'));
            $date = $timestamp->format('m/d/Y');
            $time = $timestamp->format('H') * 60 + $timestamp->format('i');
        }
        ob_start();
        ?>
        <input type="hidden" name="birs_appointment_id" id="birs_appointment_id" value="<?php echo $appointment_id; ?>">
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
                <div class="birs_error" id="birs_appointment_location_error"></div>
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
                <div class="birs_error" id="birs_appointment_service_error"></div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Provider', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_staff" name="birs_appointment_staff"
                        data-value="<?php echo $staff_id; ?>">
                    </select>
                </div>
                <div class="birs_error" id="birs_appointment_staff_error"></div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Date', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <div id="birs_appointment_datepicker"></div>
                    <input id="birs_appointment_date" name="birs_appointment_date" type="hidden" value="<?php echo $date ?>">
                </div>
                <div class="birs_error" id="birs_appointment_date_error"></div>
            </li>
            <li class="birs_form_field">
                <label>
                    <?php _e('Time', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_time" name="birs_appointment_time" size="1">
                        <?php $birchpress->util->render_html_options($options, $time); ?>
                    </select>
                </div>
                <div class="birs_error" id="birs_appointment_time_error"></div>
            </li>
        </ul>
        <?php
        return ob_get_clean();
    }

    
}

Birchschedule_View_Appointments_Edit_Imp::init_vars();

?>