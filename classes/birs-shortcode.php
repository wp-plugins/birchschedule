<?php

class BIRS_Shortcode {

    const SHORTCODE_NAME = 'birchschedule_bookingform';

    function __construct() {
        add_action('init', array(&$this, 'init'));
        add_action('template_redirect', array(&$this, 'add_js_css'));
        add_action('wp_ajax_nopriv_birs_save_appointment_frontend', array(&$this, 'ajax_save_appointment'));
        add_action('wp_ajax_birs_save_appointment_frontend', array(&$this, 'ajax_save_appointment'));
        add_action('wp_ajax_nopriv_birs_get_avaliable_time', array(&$this, 'ajax_get_avaliable_time'));
        add_action('wp_ajax_birs_get_avaliable_time', array(&$this, 'ajax_get_avaliable_time'));
    }

    function init() {
        global $birchschedule;
        add_shortcode(self::SHORTCODE_NAME, array(&$this, 'render_shortcode_html'));
        wp_register_script('underscore', $birchschedule->plugin_url() . '/assets/js/underscore.js', array(), '1.3.3');
        wp_register_script('moment', $birchschedule->plugin_url() . '/assets/js/moment.min.js', array(), '1.7.0');
        wp_register_script('birchschedule', $birchschedule->plugin_url() . '/assets/js/birchschedule.js', array('jquery-ui-datepicker', 'underscore'), '1.0');
        wp_register_style('birchschedule_styles', $birchschedule->plugin_url() . '/assets/css/birchschedule.css', array(), '1.0');
        wp_register_style('jquery-ui-bootstrap', $birchschedule->plugin_url() . '/assets/css/jquery-ui-bootstrap/jquery-ui-1.8.16.custom.css', array(), '0.23');
    }

    private function get_calendar() {
        global $birchschedule;
        return $birchschedule->get_calendar_view();
    }

    function add_js_css() {
        if (!$this->get_util()->has_shortcode(self::SHORTCODE_NAME)) {
            return;
        }
        $calendar = $this->get_calendar();
        if (is_page() || is_single()) {
            $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $time_options = $this->get_util()->get_time_options();
            $time_options = json_encode($time_options);
            $params = array(
                'ajax_url' => admin_url('admin-ajax.php', $protocol),
                'service_staff_map' => $calendar->get_service_staff_map(),
                'all_schedule' => $this->get_all_schedule(),
                'service_price_map' => $calendar->get_service_price_map()
            );
            wp_enqueue_script('birchschedule');
            wp_localize_script('birchschedule', 'birs_params', $params);

            wp_enqueue_style('jquery-ui-bootstrap');
            wp_enqueue_style('birchschedule_styles');
        }
    }

    function ajax_save_appointment() {
        $permitted = check_ajax_referer("birs_save_appointment-0", '_wpnonce', false);
        if ($permitted) {
            $errors = $this->get_calendar()->validateData();
            if (!$this->validate_time()) {
                $errors = array_merge(array(
                    'birs_appointment_datetime' => __('Time is unavaliable', 'birchschedule')
                        ), $errors);
            }
            if (!$errors) {
                $client = new BIRS_Client(0, array(
                            'meta_keys' => array(
                                '_birs_client_name_first', '_birs_client_name_last',
                                '_birs_client_email', '_birs_client_phone'
                            ),
                            'base_keys' => array(
                                'post_title'
                            )
                        ));
                $client->copyFromRequest($_POST);
                $client->load_id_by_email();
                $client_id = $client->save();
                $appointment = new BIRS_Appointment(0, array(
                            'meta_keys' => array(
                                '_birs_appointment_location', '_birs_appointment_service',
                                '_birs_appointment_staff', '_birs_appointment_timestamp',
                                '_birs_appointment_client', '_birs_appointment_duration',
                                '_birs_appointment_price', '_birs_appointment_notes'
                            ),
                            'base_keys' => array(
                                'post_title'
                            )
                        ));
                $appointment->copyFromRequest($_POST);
                $appointment['_birs_appointment_client'] = $client_id;
                $appointment_id = $appointment->save();
                if (!$appointment_id) {
                    $errors['birs_saving_appointment'] = __('Booking appointment failed');
                } else {
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
                    $time = $this->get_util()->get_wp_datetime($appointment['_birs_appointment_timestamp']);
                    $time = $time->format('l, F j - g:i A');
                }
            }
        } else {
            $errors = array(
                'birs_booking' => 'Booking appointment failed.'
            );
        }
        ?>
        <div id="birs_response">
            <?php
            if (!$errors):
                ?>
                <div id="birs_success">
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
            <?php else: ?>
                <div id="birs_errors">
                    <?php foreach ($errors as $error_id => $message): ?>
                        <p id="<?php echo $error_id; ?>"><?php echo $message; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif ?>
        </div>
        <?php
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
            $schedule = $thestaff->get_all_schedule();
            $allschedule[$thestaff->ID] = $schedule;
        }
        return $allschedule;
    }

    function get_util() {
        return BIRS_Util::getInstance();
    }

    function validate_time() {
        $avaliable_times = $this->get_avaliable_time();
        $time = $_POST['birs_appointment_time'];
        return array_key_exists($time, $avaliable_times) && $avaliable_times[$time]['avaliable'];
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
        $time_options = $this->get_avaliable_time();
        foreach ($time_options as $key => $value) {
            if ($value['avaliable']) {
                $text = $value['text'];
                echo "<option value='$key'>$text</option>";
            }
        }
        die();
    }

    public function get_service_price_text($service) {
        global $birchschedule;
        $services_view = $birchschedule->get_services_view();
        $text_map = $services_view->get_price_type_text_map();
        $price_type = $service['_birs_service_price_type'];
        if ($price_type == 'fixed') {
            return '$' . $service['_birs_service_price'];
        } else if ($price_type == 'dont-show') {
            return '';
        } else {
            return $text_map[$price_type];
        }
    }

    function render_service_options($selection) {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_service'
                        ),
                        array(
                            'meta_keys' => array(
                                '_birs_service_length', '_birs_service_length_type',
                                '_birs_service_padding', '_birs_service_padding_type',
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
            $length = $service->get_service_length();
            $price = $this->get_service_price_text($service);
            echo "<option value='$service->ID' $selected>$service->post_title ($length mins) - $price</option>";
        };
    }

    function render_staff_options($service_id, $selection) {
        $service = new BIRS_Service($service_id, array(
                    'meta_keys' => array(
                        '_birs_assigned_staff'
                    )
                ));
        $service->load();
        $assigned_staff = $service->get_assigned_staff_ids();
        $query = new BIRS_Model_Query(
                        array('post_type' => 'birs_staff'),
                        array(
                            'base_keys' => 'post_title'
                        )
        );
        $all_staff = $query->query();
        foreach (array_values($all_staff) as $staff) {
            if ($staff->ID == $selection) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            if (array_key_exists($staff->ID, $assigned_staff)) {
                echo "<option value='$staff->ID' $selected>$staff->post_title</option>";
            }
        }
    }

    function render_shortcode_html($attr) {
        $calendar = $this->get_calendar();
        ob_start();
        ?>
        <div class="birchschedule" id="birs_booking_box">
            <form id="birs_appointment_form">
                <input type="hidden" id="birs_appointment_price" name="birs_appointment_price">
                <div>
                    <?php wp_nonce_field("birs_save_appointment-0"); ?>
                    <ul>
                        <li>
                            <label><?php _e('Location', 'birchschedule'); ?></label>
                            <div>
                                <select id="birs_appointment_location" name="birs_appointment_location">
                                    <?php $this->render_location_options(); ?>
                                </select>
                            </div>
                        </li>
                        <li>
                            <label><?php _e('Service', 'birchschedule'); ?></label>
                            <div>
                                <select id="birs_appointment_service" name="birs_appointment_service">
                                    <?php $this->render_service_options(0); ?>
                                </select>
                            </div>
                        </li>
                        <li>
                            <label><?php _e('Service Provider', 'birchschedule'); ?></label>
                            <div>
                                <select id="birs_appointment_staff" name="birs_appointment_staff">
                                    <?php $this->render_staff_options(0, 0); ?>
                                </select>
                            </div>
                            <p class="error" id="birs_appointment_service_error"></p>
                        </li>
                        <li>
                            <label><?php _e('Date & Time', 'birchschedule'); ?></label>
                            <div class="datetime">
                                <input id="birs_appointment_date" name="birs_appointment_date" type="text">
                                <select id="birs_appointment_time" name="birs_appointment_time">
                                    <?php $calendar->render_time_options(0); ?>
                                </select>
                                <div class="clear"></div>
                            </div>
                            <p class="error" id="birs_appointment_datetime_error"></p>
                        </li>
                        <li>
                            <label><?php _e('First Name', 'birchschedule') ?></label>
                            <div>
                                <input id="birs_client_name_first" name="birs_client_name_first" type="text">
                            </div>
                            <p class="error" id="birs_client_name_first_error"></p>
                        </li>
                        <li>
                            <label><?php _e('Last Name', 'birchschedule') ?></label>
                            <div>
                                <input id="birs_client_name_last" name="birs_client_name_last" type="text">
                            </div>
                            <p class="error" id="birs_client_name_last_error"></p>
                        </li>
                        <li>
                            <label><?php _e('Email', 'birchschedule') ?></label>
                            <div>
                                <input id="birs_client_email" name="birs_client_email" type="text">
                            </div>
                            <p class="error" id="birs_client_email_error"></p>
                        </li>
                        <li>
                            <label><?php _e('Phone', 'birchschedule') ?></label>
                            <div>
                                <input id="birs_client_phone" name="birs_client_phone" type="text">
                            </div>
                            <p class="error" id="birs_client_phone_error"></p>
                        </li>
                        <li>
                            <label><?php _e('Notes', 'birchschedule') ?></label>
                            <div>
                                <textarea id="birs_appointment_notes" name="birs_appointment_notes"></textarea>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="footer">
                    <p class="error" id="birs_booking_error"></p>
                    <input type="button" value="<?php _e('Submit', 'birchschedule'); ?>" class="button" id="birs_book_appointment">
                </div>
            </form>
        </div>
        <div id="birs_booking_success">
        </div>
        <?php
        return ob_get_clean();
    }

    function render_location_options() {
        $locations = get_posts(array('post_type' => 'birs_location'));
        foreach ($locations as $location) {
            echo "<option value='$location->ID'>" . $location->post_title . "</option>";
        }
    }

}