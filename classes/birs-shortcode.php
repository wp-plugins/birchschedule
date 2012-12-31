<?php

class BIRS_Shortcode {

    const SHORTCODE_NAME = 'bp-scheduler-bookingform';

    function __construct() {
        add_action('init', array(&$this, 'init'));
        add_action('template_redirect', array(&$this, 'add_js_css'));
        add_action('wp_ajax_nopriv_birs_save_appointment_frontend', array(&$this, 'ajax_save_appointment'));
        add_action('wp_ajax_birs_save_appointment_frontend', array(&$this, 'ajax_save_appointment'));
        add_action('wp_ajax_nopriv_birs_get_avaliable_time', array(&$this, 'ajax_get_avaliable_time'));
        add_action('wp_ajax_birs_get_avaliable_time', array(&$this, 'ajax_get_avaliable_time'));
    }

    function init() {
        add_shortcode(self::SHORTCODE_NAME, array(&$this, 'get_shortcode_html'));
        add_filter('birchschedule_validate_booking_form_info', array($this, 'validate_time'));
        add_filter('birchschedule_booking_form_fields', array($this, 'get_form_fields_html'));
    }

    private function get_calendar_view() {
        global $birchschedule;
        return $birchschedule->calendar_view;
    }

    function add_js_css() {
        if (!$this->get_util()->has_shortcode(self::SHORTCODE_NAME)) {
            return;
        }
        $calendar = $this->get_calendar_view();
        if (is_page() || is_single()) {
            $params = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'all_schedule' => $this->get_all_schedule(),
                'service_price_map' => $calendar->get_service_price_map(),
                'service_staff_map' => $calendar->get_service_staff_map(),
                'location_staff_map' => $calendar->get_location_staff_map(),
                'gmt_offset' =>$this->get_util()->get_gmt_offset()
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
            $calendar = $this->get_calendar_view();
            $errors = apply_filters('birchschedule_validate_booking_form_info', array());
            if (!$errors) {
                $appointment_id = $calendar->save_appointment();
                if (!$appointment_id) {
                    $errors['birs_saving_appointment'] = __('Booking appointment failed');
                } else {
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
        return BIRS_Util::get_instance();
    }

    function render_service_options($selection) {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_service'
                        ),
                        array(
                            'meta_keys' => array(
                                '_birs_service_length', '_birs_service_length_type', '_birs_service_price', '_birs_service_price_type'
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
        if ($selection) {
            return $selection;
        } else {
            return key($services);
        }
    }

    function validate_time($errors) {
        $avaliable_times = $this->get_avaliable_time();
        $time = $_POST['birs_appointment_time'];
        $valid = array_key_exists($time, $avaliable_times) && $avaliable_times[$time]['avaliable'];
        if (!$valid) {
            $errors = array_merge(
                    array(
                'birs_appointment_datetime' => __('Time is unavaliable', 'birchschedule'
                    )), $errors);
        }
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
        foreach ($time_options as $key => $value) {
            if ($value['avaliable']) {
                $text = $value['text'];
                echo "<span><a data-time='$key' href='javascript:void(0)'>$text</a></span>";
            }
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
        if (!is_page() && !is_single()) {
            return;
        }
        $calendar = $this->get_calendar_view();
        ob_start();
        ?>
        <div class="birchschedule" id="birs_booking_box">
            <form id="birs_appointment_form">
                <input type="hidden" id="birs_appointment_price" name="birs_appointment_price">
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
        ob_start();
        ?>
        <ul>
        <li class="birs_form_field">
            <h2 class="birs_section"><?php _e('Appointment Info', 'birchschedule'); ?></h2>
        </li>
        <li class="birs_form_field">
            <label><?php _e('Location', 'birchschedule'); ?></label>
            <div>
                <select id="birs_appointment_location" name="birs_appointment_location">
                    <?php $location_id = $calendar->render_location_options(); ?>
                </select>
            </div>
        </li>
        <li class="birs_form_field">
            <label><?php _e('Service', 'birchschedule'); ?></label>
            <div>
                <select id="birs_appointment_service" name="birs_appointment_service">
                    <?php $service_id = $this->render_service_options(0); ?>
                </select>
            </div>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('Service Provider', 'birchschedule'); ?></label>
            <div>
                <select id="birs_appointment_staff" name="birs_appointment_staff">
                    <?php $calendar->render_staff_options($location_id, $service_id, 0); ?>
                </select>
            </div>
            <div class="birs_error" id="birs_appointment_service_error"></div>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('Date & Time', 'birchschedule'); ?></label>
            <input id="birs_appointment_date" name="birs_appointment_date" type="hidden">
            <input id="birs_appointment_time" name="birs_appointment_time" type="hidden">
            <table class="birs_datetime">
                <tbody>
                    <tr>
                        <td><div id="birs_appointment_datepicker"></div></td>
                        <td><div id="birs_appointment_timeoptions"></div></td>
                    </tr>
                </tbody>
            </table>
            <div class="birs_error" id="birs_appointment_datetime_error"></div>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('Notes', 'birchschedule') ?></label>
            <div>
                <textarea id="birs_appointment_notes" name="birs_appointment_notes"></textarea>
                <input type="hidden" name="birs_appointment_fields[]" value="_birs_appointment_notes" />
            </div>
        </li>
        <li class="birs_form_field">
            <h2 class="birs_section"><?php _e('Your Info', 'birchschedule'); ?></h2>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('First Name', 'birchschedule') ?></label>
            <div>
                <input id="birs_client_name_first" name="birs_client_name_first" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_first" />
            </div>
            <div class="birs_error" id="birs_client_name_first_error"></div>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('Last Name', 'birchschedule') ?></label>
            <div>
                <input id="birs_client_name_last" name="birs_client_name_last" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_last" />
            </div>
            <div class="birs_error" id="birs_client_name_last_error"></div>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('Email', 'birchschedule') ?></label>
            <div>
                <input id="birs_client_email" name="birs_client_email" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_email" />
            </div>
            <div class="birs_error" id="birs_client_email_error"></div>
        </li>
        <li class="birs_form_field"> 
            <label><?php _e('Phone', 'birchschedule') ?></label>
            <div>
                <input id="birs_client_phone" name="birs_client_phone" type="text">
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_phone" />
            </div>
            <div class="birs_error" id="birs_client_phone_error"></div>
        </li>
        <li class="birs_footer"> 
            <div class="birs_error" id="birs_booking_error"></div>
            <div>
                <input type="button" value="<?php _e('Submit', 'birchschedule'); ?>" class="button" id="birs_book_appointment">
            </div>
        </li>
        </ul>
        <?php
        $html = ob_get_clean();
        return $html;
    }

}