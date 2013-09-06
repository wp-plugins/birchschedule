<?php

class BIRS_Calendar_View extends BIRS_Admin_View {
    var $calendar_logic;
    private static $instance;

    static function get_instance() {
        if(!self::$instance) {
            self::$instance = new BIRS_Calendar_View();
        }
        return self::$instance;
    }

    function __construct() {
        parent::__construct();
        $this->calendar_logic = BIRS_Calendar_Logic::get_instance();
    }

    function init() {
    }

    function admin_init() {
        parent::admin_init();
        add_action('wp_ajax_birs_query_appointments', array(&$this, 'ajax_query_appointments'));
        add_action('wp_ajax_nopriv_birs_query_appointments', 
            array($this, 'ajax_query_appointments'));
        add_action('wp_ajax_birs_render_edit_form', array(&$this, 'ajax_render_edit_form'));
        add_action('wp_ajax_birs_save_appointment', array(&$this, 'ajax_save_appointment'));
        add_action('wp_ajax_birs_delete_appointment', array(&$this, 'ajax_delete_appointment'));
        add_filter('birchschedule_general_appointment_section_admin_edit',
            array($this, 'get_general_appointment_section_html'), 10, 4);
        add_filter('birchschedule_general_client_section_admin_edit', array($this, 'get_general_client_section_html'), 10, 2);
        add_filter('birchschedule_client_details_admin_edit', array($this, 'get_client_details_html'), 10, 3);
        add_filter('birchschedule_appointment_details_admin_edit', array($this, 'get_appointment_details_html'), 10, 2);
        add_filter('birchschedule_appointment_details_admin_edit_duration', array($this, 'get_appointment_duration_html'), 10, 2);
        register_post_type('birs_appointment', array(
            'labels' => array(
                'name' => __('Appointments', 'birchschedule'),
                'singular_name' => __('Appointment', 'birchschedule'),
                'add_new' => __('Add Appointment', 'birchschedule'),
                'add_new_item' => __('Add New Appointment', 'birchschedule'),
                'edit' => __('Edit', 'birchschedule'),
                'edit_item' => __('Edit Appointment', 'birchschedule'),
                'new_item' => __('New Appointment', 'birchschedule'),
                'view' => __('View Appointment', 'birchschedule'),
                'view_item' => __('View Appointment', 'birchschedule'),
                'search_items' => __('Search Appointments', 'birchschedule'),
                'not_found' => __('No Appointments found', 'birchschedule'),
                'not_found_in_trash' => __('No Appointments found in trash', 'birchschedule'),
                'parent' => __('Parent Appointment', 'birchschedule')
            ),
            'description' => __('This is where appointments are stored.', 'birchschedule'),
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
                )
        );
        if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'birchschedule_calendar') {
            do_action('birchschedule_view_calendar_admin_init');
            do_action('birchschedule_add_admin_scripts_view_calendar');
        }
    }

    function get_admin_scripts() {
        $scripts = array();
        $save_button_html = '<input type="button" class="button-primary tips" name="save_appointment" id="save_appointment" value="Save" alt="Save Data" onclick="javascript:void(0)">';
        $delete_button_html = '<a id="delete_appointment" class="submitdelete deletion" href="javascript:void(0)">' . __('Delete', 'birchschedule') . '</a>';

        $add_appointment_title = '<div>' . $save_button_html .
                '<span>' . __('Add Appointment', 'birchschedule') . '</span>' . '</div>';
        $edit_appointment_title = '<div class="submitbox">' .
                $save_button_html .
                $delete_button_html .
                '<span>' . __('Edit Appointment', 'birchschedule') . '</span>' . '</div>';
        $gmt_offset = $this->get_util()->get_gmt_offset();
        $fc_time_format = $this->get_util()->date_time_format_php_to_fullcalendar(get_option('time_format', 'g:i a'));
        $params = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'default_calendar_view' => apply_filters('birchschedule_default_calendar_view', 'agendaWeek'),
            'add_appointment_title' => $add_appointment_title,
            'edit_appointment_title' => $edit_appointment_title,
            'gmt_offset' => $gmt_offset,
            'fc_time_format' => $fc_time_format,
            'location_map' => apply_filters('birchschedule_location_map', array()),
            'location_staff_map' => apply_filters('birchschedule_location_staff_map_admin', array()),
            'location_order' => apply_filters('birchschedule_location_listing_order', array()),
            'staff_order' => apply_filters('birchschedule_staff_listing_order', array()),
            'fc_i18n_options' => $this->get_util()->get_fullcalendar_i18n_params(),
            'i18n' => array(
                'loading' => __('Loading...', 'birchschedule'),
                'loading_appointments' => __('Loading appointments...', 'birchschedule'),
                'Saving' => __('Saving...', 'birchschedule'),
                'Save' => __('Save', 'birchschedule')
            )
        );
        $scripts[] = array('birs_admin_calendar', 'birs_calendar_params', $params);
        $jquery_date_format = $this->get_util()->date_time_format_php_to_jquery(get_option('date_format'));
        $appointment_edit_params = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'service_staff_map' => $this->calendar_logic->get_service_staff_map(),
            'service_price_map' => $this->calendar_logic->get_service_price_map(),
            'service_duration_map' => $this->calendar_logic->get_service_duration_map(),
            'location_map' => apply_filters('birchschedule_location_map', array()),
            'location_staff_map' => apply_filters('birchschedule_location_staff_map_admin', array()),
            'location_service_map' => apply_filters('birchschedule_location_service_map', array()),
            'location_order' => apply_filters('birchschedule_location_listing_order', array(), false),
            'staff_order' => apply_filters('birchschedule_staff_listing_order', array(), false),
            'service_order' => apply_filters('birchschedule_service_listing_order', array()),
            'jquery_date_format' => $jquery_date_format,
            'datepicker_i18n_options' => $this->get_util()->get_datepicker_i18n_params()
        );
        $scripts[] = array('birs_admin_appointment_edit', 'birs_appointment_edit_params', $appointment_edit_params);
        return $scripts;
    }

    function get_admin_styles() {
        return array('jquery-ui-bootstrap', 'birs_lib_fullcalendar', 
            'birchschedule_admin_styles', 'birchschedule_admin_calendar', 
            'select2', 'jgrowl');
    }

    function ajax_query_appointments() {
        $start = $_GET['birs_time_start'];
        $start = $this->get_util()->get_wp_datetime($start)->format('U');
        $end = $_GET['birs_time_end'];
        $end = $this->get_util()->get_wp_datetime($end)->format('U');
        $location_id = $_GET['birs_location_id'];
        $staff_id = $_GET['birs_staff_id'];
        $appointments = 
            $this->calendar_logic->query_appointments($start, $end, $location_id, $staff_id);
        $apmts = array();
        foreach ($appointments as $appointment) {
            $post_id = $appointment['ID'];
            $duration = get_post_meta($post_id, '_birs_appointment_duration', true);
            $duration = intval($duration);
            $price = get_post_meta($post_id, '_birs_appointment_price', true);
            $time_start = get_post_meta($post_id, '_birs_appointment_timestamp', true);
            $time_end = $time_start + $duration * 60;
            $time_start = $this->get_util()->get_wp_datetime($time_start)->format('c');
            $time_end = $this->get_util()->get_wp_datetime($time_end)->format('c');
            $apmt = array(
                'id' => $appointment['ID'],
                'title' => $appointment['post_title'],
                'start' => $time_start,
                'end' => $time_end,
                'allDay' => false,
                'editable' => true
            );
            $apmts[] = $apmt;
        }
        $apmts = apply_filters('birchschedule_query_appointments', $apmts, $staff_id, $start, $end);
        ?>
        <div id="birs_response">
            <?php
            echo json_encode($apmts);
            ?>
        </div>
        <?php
        exit;
    }

    function render_admin_page() {
        do_action('birchschedule_show_update_notice');
        ?>
        <div class="birchschedule wrap">
            <h2 id="birs_calendar_title">
                <?php _e('Calendar', 'birchschedule'); ?>&nbsp;
                <a href="javascript:void(0)"
                   id="birs_add_appointment"
                   class="add-new-h2">
                       <?php _e('Add Appointment', 'birchschedule'); ?>
                </a>
                <a href="javascript:void(0)"
                   id="birs_calendar_refresh"
                   class="add-new-h2">
                       <?php _e('Refresh', 'birchschedule'); ?>
                </a>
            </h2>
            <div id="birs_calendar_toolbar">
                <div id="birs_calendar_filter">
                    <label><?php _e('Location', 'birchschedule'); ?></label>
                    <select id="birs_calendar_location">
                    </select>
                    <label><?php _e('Staff', 'birchschedule'); ?></label>
                    <select id="birs_calendar_staff">
                    </select>
                </div>
                <div class="clear"></div>
            </div>
            <div  id="birs_calendar"></div>
            <div id="birs_add_new_dialog">
            </div>
        </div>
        <?php
    }

    function get_client_details_html($html, $client_id) {
        $client_titles = $this->get_util()->get_client_title_options();
        $states = $this->get_util()->get_us_states();
        $countries = $this->get_util()->get_countries();
        $client_title = '';
        $address1 = '';
        $address2 = '';
        $city = '';
        $state = '';
        $province = '';
        $zip = '';
        $country = false;
        $default_country = apply_filters('birchschedule_default_country', 'US');
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
                        <?php $this->get_util()->render_html_options($client_titles, $client_title); ?>
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
                        <?php $this->get_util()->render_html_options($states, $state); ?>
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
                        $this->get_util()->render_html_options($countries, $country, $default_country);
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

    function get_appointment_details_html($html, $appointment_id) {
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

    function ajax_render_edit_form() {
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
                            echo apply_filters('birchschedule_general_appointment_section_admin_edit',
                                '', $appointment_id, $location_id, $staff_id);
                        ?>
                    </div>
                    <div class="splitter"></div>
                    <div id="birs_general_section_client">
                        <?php echo apply_filters('birchschedule_general_client_section_admin_edit', '', $client_id);
                        ?>
                    </div>
                </div>
                <div id="birs_appointment_details">
                    <?php
                    echo apply_filters('birchschedule_appointment_details_admin_edit', '', $appointment_id);
                    ?>
                </div>
                <div id="birs_client_details">
                    <?php
                    echo apply_filters('birchschedule_client_details_admin_edit', '', $client_id,
                        array('client_name_first', 'client_name_last', 'client_email', 'client_phone'));
                    ?>
                </div>
                <div id="birs_appointment_payments_details">
                    <?php
                    echo apply_filters('birchschedule_appointment_payments_details_admin_edit', '', $appointment_id);
                    ?>
                </div>
            </form>
        </div>
        <?php
        exit();
    }
    
    function get_appointment_duration_html($html, $appointment_duration) {
        ob_start();
        ?>
        <input type="hidden" id="birs_appointment_duration"
                name="birs_appointment_duration"
                value="<?php echo $appointment_duration; ?>" />
        <?php
        return ob_get_clean();
    }
    
    function get_general_appointment_section_html($html, $appointment_id,
        $location_id, $staff_id) {
        $timestamp = time();
        $price = 0;
        $service_id = 0;
        $date = '';
        $date4picker='';
        $time = 540;
        $appointment_duration = 0;
        if($appointment_id) {
            $timestamp = get_post_meta($appointment_id, '_birs_appointment_timestamp', true);
            $timestamp = $this->get_util()->get_wp_datetime($timestamp);
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
                        <?php $this->render_time_options($time); ?>
                    </select>
                    <div class="birs_error" id="birs_appointment_time_error"></div>
                </div>
            </li>
            <?php echo apply_filters('birchschedule_appointment_details_admin_edit_duration', '', $appointment_duration); ?>
        </ul>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    function get_general_client_section_html($html, $client_id) {
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

    function ajax_save_appointment() {
        if (isset($_POST['birs_appointment_id'])) {
            $appointment_id = $_POST['birs_appointment_id'];
        } else {
            $appointment_id = 0;
        }
        $permitted = check_ajax_referer("birs_save_appointment-$appointment_id", '_wpnonce', false);
        if($permitted) {
            $errors = apply_filters('birchschedule_validate_booking_info_admin', array());
            if (!$errors) {
                $this->calendar_logic->save_appointment();
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

    function ajax_delete_appointment() {
        $appointment_id = $_REQUEST['birs_appointment_id'];
        check_ajax_referer("birs_delete_appointment-$appointment_id");
        $appointment = new BIRS_Appointment($appointment_id);
        $appointment->delete();
        die;
    }
    
    function render_time_options($selection) {
        $options = $this->get_util()->get_time_options(5);
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