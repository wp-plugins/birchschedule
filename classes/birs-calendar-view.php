<?php

class BIRS_Calendar_View extends BIRS_Admin_View {

    private $service_staff_map = array();

    function __construct() {
        add_action('admin_init', array(&$this, 'admin_init'));
    }

    function admin_init() {
        global $birchschedule;
        $hook_suffix = $birchschedule->calendar_hook_suffix;
        add_action('admin_print_styles-' . $hook_suffix, array(&$this, 'add_admin_css'));
        add_action('admin_print_scripts-' . $hook_suffix, array(&$this, 'add_admin_scripts'));
        add_action('wp_ajax_birs_query_appointments', array(&$this, 'ajax_query_appointments'));
        add_action('wp_ajax_birs_render_edit_form', array(&$this, 'ajax_render_edit_form'));
        add_action('wp_ajax_birs_save_appointment', array(&$this, 'ajax_save_appointment'));
        add_action('wp_ajax_birs_delete_appointment', array(&$this, 'ajax_delete_appointment'));
        $this->register_js_css();
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
        $this->service_staff_map = $this->get_service_staff_map();
    }

    function register_js_css() {
        global $birchschedule;
        wp_register_script('birs_lib_fullcalendar', $birchschedule->plugin_url() . '/assets/js/fullcalendar/fullcalendar.js', array('jquery-ui-draggable', 'jquery-ui-resizable',
            'jquery-ui-dialog', 'jquery-ui-datepicker',
            'jquery-ui-tabs', 'jquery-ui-autocomplete'), '1.5.3');
        wp_register_script('birs_admin_common', $birchschedule->plugin_url() . '/assets/js/admin/common.js', array('jquery'), '1.0');
        wp_register_script('birs_calendar', $birchschedule->plugin_url() . '/assets/js/admin/calendar.js', array('birs_lib_fullcalendar', 'moment', 'birs_admin_common'), '1.0');
        wp_register_style('birs_lib_fullcalendar', $birchschedule->plugin_url() . '/assets/js/fullcalendar/fullcalendar.css', array(), '1.5.3');
        wp_register_style('birchschedule_admin_styles', $birchschedule->plugin_url() . '/assets/css/admin.css', array(), '1.0');
        wp_register_style('jquery-ui-bootstrap', $birchschedule->plugin_url() . '/assets/css/jquery-ui-bootstrap/jquery-ui-1.8.16.custom.css', array(), '0.23');
        wp_register_style('jquery-wijmo-open', $birchschedule->plugin_url() . '/assets/css/jquery-ui-bootstrap/jquery.wijmo-open.1.5.0.css', array(), '1.5.0');
    }

    function add_admin_scripts() {
        wp_enqueue_script('birs_calendar');
        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $save_button_html = '<input type="button" class="button-primary tips" name="save_appointment" id="save_appointment" value="Save" alt="Save Data" onclick="javascript:void(0)">';
        $delete_button_html = '<a id="delete_appointment" class="submitdelete deletion" href="javascript:void(0)">' . __('Delete', 'birchschedule') . '</a>';

        $add_appointment_title = '<div>' . $save_button_html .
                '<span>' . __('Add Appointment', 'birchschedule') . '</span>' . '</div>';
        $edit_appointment_title = '<div class="submitbox">' .
                $save_button_html .
                $delete_button_html .
                '<span>' . __('Edit Appointment', 'birchschedule') . '</span>' . '</div>';
        $gmt_offset = -round($this->get_util()->get_wp_datetime(time())->getOffset() / 60);
        $params = array('ajax_url' => admin_url('admin-ajax.php', $protocol),
            'add_appointment_title' => $add_appointment_title,
            'edit_appointment_title' => $edit_appointment_title,
            'service_staff_map' => $this->service_staff_map,
            'service_price_map' => $this->get_service_price_map(),
            'gmt_offset' => $gmt_offset);
        wp_localize_script('birs_calendar', 'birs_params', $params);
    }

    function add_admin_css() {
        wp_enqueue_style('jquery-ui-bootstrap');
        wp_enqueue_style('birs_lib_fullcalendar');
        wp_enqueue_style('birchschedule_admin_styles');
    }

    function get_service_price_map() {
        $query = new BIRS_Model_Query(
                        array('post_type' => 'birs_service'),
                        array(
                            'meta_keys' => array('_birs_service_price', '_birs_service_price_type'))
        );
        $services = $query->query();
        $price_map = array();
        foreach ($services as $service) {
            $price_map[$service['ID']] = array(
                'price' => $service['_birs_service_price'],
                'price_type' => $service['_birs_service_price_type']
            );
        }
        return $price_map;
    }

    function get_service_staff_map() {
        $map_init_value = array();
        if (is_admin()) {
            $map_init_value = array(0 => array(0 => __("Select Staff...", 'birchschedule')));
        }
        $map = $map_init_value;
        $services = get_posts(array('post_type' => 'birs_service'));
        foreach ($services as $service) {
            $staff = get_post_meta($service->ID, '_birs_assigned_staff', true);
            $staff = unserialize($staff);
            if ($staff === false) {
                $staff = array();
            }
            $assigned_staff = $map_init_value;
            foreach ($staff as $staff_id => $val) {
                $thestaff = get_post($staff_id);
                $assigned_staff[$staff_id] = $thestaff->post_title;
            }
            $map[$service->ID] = $assigned_staff;
        }
        return $map;
    }

    function ajax_query_appointments() {
        $start = $_GET['birs_time_start'];
        $start = $this->get_util()->get_wp_datetime($start)->format('U');
        $end = $_GET['birs_time_end'];
        $end = $this->get_util()->get_wp_datetime($end)->format('U');
        $location_id = $_GET['birs_location_id'];
        $staff_id = $_GET['birs_staff_id'];
        $appointments = get_posts(array(
            'post_type' => 'birs_appointment',
            'nopaging' => true,
            'meta_query' => array(
                array('key' => '_birs_appointment_timestamp',
                    'value' => $start,
                    'compare' => '>=',
                    'type' => 'SIGNED'
                ), array('key' => '_birs_appointment_timestamp',
                    'value' => $end,
                    'compare' => '<=',
                    'type' => 'SIGNED'),
                array('key' => '_birs_appointment_location',
                    'value' => $location_id,
                    'type' => 'UNSIGNED'),
                array('key' => '_birs_appointment_staff',
                    'value' => $staff_id,
                    'type' => 'UNSIGNED'))));
        $apmts = array();
        foreach ($appointments as $appointment) {
            $post_id = $appointment->ID;
            $duration = get_post_meta($post_id, '_birs_appointment_duration', true);
            $duration = intval($duration);
            $price = get_post_meta($post_id, '_birs_appointment_price', true);
            $time_start = get_post_meta($post_id, '_birs_appointment_timestamp', true);
            $time_end = $time_start + $duration * 60;
            $time_start = $this->get_util()->get_wp_datetime($time_start)->format('c');
            $time_end = $this->get_util()->get_wp_datetime($time_end)->format('c');
            $apmt = array('id' => $appointment->ID,
                'title' => $appointment->post_title,
                'start' => $time_start,
                'end' => $time_end,
                'allDay' => false);
            $apmts[] = $apmt;
        }
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
        $locations = get_posts(array('post_type' => 'birs_location'));
        $staff = get_posts(array('post_type' => 'birs_staff'));
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
            <p id="birs_calendar_status" class="update-nag"><?php _e('Loading...', 'birchschedule'); ?></p>
            <div id="birs_calendar_filter">
                <label>Location<select id="birs_calendar_location">
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location->ID; ?>"><?php echo $location->post_title; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>                    
                <label>Staff
                    <select id="birs_calendar_staff">
                        <?php foreach ($staff as $thestaff): ?>
                            <option value="<?php echo $thestaff->ID; ?>"><?php echo $thestaff->post_title; ?></option>
                        <?php endforeach; ?>
                    </select></label>
            </div>
            <div class="clear"></div>
            <div  id="birs_calendar"></div>
            <div id="birs_add_new_dialog">
            </div>
        </div>
        <?php
    }

    function ajax_render_edit_form() {
        $client_titles = $this->get_util()->get_client_title_options();
        $states = $this->get_util()->get_us_states();
        $countries = $this->get_util()->get_countries();

        $appointment_id = 0;
        $timestamp = time();
        $price = 0;
        $service_id = 0;
        $staff_id = 0;
        if (isset($_REQUEST['birs_appointment_staff'])) {
            $staff_id = $_REQUEST['birs_appointment_staff'];
        }
        $date = '';
        $time = 540;
        $client_title = '';
        $first_name = '';
        $last_name = '';
        $email = '';
        $phone = '';
        $notes = '';
        $address1 = '';
        $address2 = '';
        $city = '';
        $state = '';
        $zip = '';
        if (isset($_GET['birs_appointment_id'])) {
            $appointment_id = $_GET['birs_appointment_id'];
            $timestamp = get_post_meta($appointment_id, '_birs_appointment_timestamp', true);
            $timestamp = $this->get_util()->get_wp_datetime($timestamp);
            $date = $timestamp->format('m/d/Y');
            $time = $timestamp->format('H') * 60 + $timestamp->format('i');

            $price = get_post_meta($appointment_id, '_birs_appointment_price', true);
            $service_id = get_post_meta($appointment_id, '_birs_appointment_service', true);
            $staff_id = get_post_meta($appointment_id, '_birs_appointment_staff', true);
            $client_id = get_post_meta($appointment_id, '_birs_appointment_client', true);
            $notes = get_post_meta($appointment_id, '_birs_appointment_notes', true);
            if ($client_id) {
                $client_title = get_post_meta($client_id, '_birs_client_title', true);
                $first_name = get_post_meta($client_id, '_birs_client_name_first', true);
                $last_name = get_post_meta($client_id, '_birs_client_name_last', true);
                $email = get_post_meta($client_id, '_birs_client_email', true);
                $phone = get_post_meta($client_id, '_birs_client_phone', true);
                $address1 = get_post_meta($client_id, '_birs_client_address1', true);
                $address2 = get_post_meta($client_id, '_birs_client_address2', true);
                $city = get_post_meta($client_id, '_birs_client_city', true);
                $state = get_post_meta($client_id, '_birs_client_state', true);
                $country = get_post_meta($client_id, '_birs_client_country', true);
                $zip = get_post_meta($client_id, '_birs_client_zip', true);
            }
        }
        ?>
        <div id="birs_appointment_edit">
            <ul>
                <li><a href="#birs_appointment_info"><?php _e('Appointment Info', 'birchschedule'); ?></a></li>
                <li><a href="#birs_client_details"><?php _e('Client Details', 'birchschedule'); ?></a></li>
            </ul>
            <form id="birs_appointment_form">
                <div class="wrap" id="birs_appointment_info">
                    <?php wp_nonce_field("birs_save_appointment-$appointment_id"); ?>
                    <?php wp_nonce_field("birs_delete_appointment-$appointment_id", 'birs_delete_appointment_nonce', false); ?>
                    <input type="hidden" name="birs_appointment_id" id="birs_appointment_id" value="<?php echo $appointment_id; ?>">
                    <table>
                        <tr>
                            <th>
                                <label><?php _e('Service', 'birchschedule'); ?></label>
                            </th>
                            <td>
                                <select id="birs_appointment_service" name="birs_appointment_service"><?php $this->render_service_options($staff_id, $service_id); ?></select>
                            </td>
                            <th>
                                <label><?php _e('with', 'birchschedule'); ?></label></th>
                            <td>
                                <select id="birs_appointment_staff" name="birs_appointment_staff"><?php $this->render_staff_options($staff_id); ?></select>
                            </td>
                        </tr>
                        <tr class="error">
                            <th></th>
                            <td colspan="3">
                                <label id="birs_appointment_service_error" class="error"></label>
                            </td>
                        </tr>
                        <tr>
                            <th class="birs-first-head">
                                <label><?php _e('Date & Time', 'birchschedule'); ?></label>
                            </th>
                            <td colspan="3">
                                <input id="birs_appointment_date" name="birs_appointment_date" type="text" value="<?php echo $date ?>">
                                <select id="birs_appointment_time" name="birs_appointment_time">
                                    <?php $this->render_time_options($time); ?>
                                </select></td>
                        </tr>
                        <tr class="error">
                            <th></th>
                            <td colspan="3">
                                <label id="birs_appointment_datetime_error" class="error"></label>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label><?php _e('Price', 'birchschedule'); ?></label>
                            </th>
                            <td colspan="3">
                                <input type="text" id="birs_appointment_price" name="birs_appointment_price" value="<?php echo $price; ?>">
                            </td>
                        </tr>
                    </table>
                    <div class="splitter"></div>
                    <table>
                        <tr>
                            <th class="birs-first-head">
                                <label><?php _e('First Name', 'birchschedule'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="birs_client_name_first" name="birs_client_name_first" value="<?php echo $first_name; ?>">
                            </td>
                            <th><label><?php _e('Title', 'birchschedule'); ?></label></th>
                            <td>
                                <select name="birs_client_title" id="birs_client_title">
                                    <?php $this->get_util()->render_html_options($client_titles, $client_title); ?>
                                </select>
                            </td>
                        </tr>
                        <tr class="error">
                            <th></th>
                            <td colspan="3">
                                <label id="birs_client_name_first_error" class="error"></label>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label><?php _e('Last Name', 'birchschedule'); ?></label>
                            </th>
                            <td colspan="3">
                                <input type="text" id="birs_client_name_last" name="birs_client_name_last" value="<?php echo $last_name; ?>">
                            </td>  
                        </tr>
                        <tr class="error">
                            <th></th>
                            <td colspan="3">
                                <label id="birs_client_name_last_error" class="error"></label>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label><?php _e('Email', 'birchschedule'); ?></label>
                            </th>
                            <td colspan="3">
                                <input type="text" id="birs_client_email" name="birs_client_email" value="<?php echo $email; ?>">
                            </td>
                        </tr>
                        <tr class="error">
                            <th></th>
                            <td colspan="3">
                                <label id="birs_client_email_error" class="error"></label>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label><?php _e('Phone', 'birchschedule'); ?></label>
                            </th>
                            <td colspan="3">
                                <input type="text" id="birs_client_phone" name="birs_client_phone" value="<?php echo $phone; ?>">
                            </td>
                        </tr>
                        <tr class="error">
                            <th></th>
                            <td colspan="3">
                                <label id="birs_client_phone_error" class="error"></label>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label><?php _e('Notes', 'birchschedule'); ?></label>
                            </th>
                            <td colspan="3">
                                <textarea id="birs_appointment_notes" name="birs_appointment_notes"><?php echo $notes; ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>
                <div id="birs_client_details">
                    <table>
                        <tr>
                            <th class="birs-first-head"><label><?php _e('Address', 'birchschedule'); ?></label></th>
                            <td><input type="text" name="birs_client_address1" id="birs_client_address1" value="<?php echo $address1; ?>"></td>
                        </tr>
                        <tr>
                            <th><label></label></th>
                            <td><input type="text" name="birs_client_address2" id="birs_client_address2" value="<?php echo $address2; ?>"></td>
                        </tr>
                        <tr>
                            <th><label><?php _e('City', 'birchschedule') ?></label></th>
                            <td><input type="text" name="birs_client_city" id="birs_client_city" value="<?php echo $city; ?>"></td>
                        </tr>
                        <tr>
                            <th><label><?php _e('State/Province', 'birchschedule') ?></label></th>
                            <td>
                                <select name="birs_client_state" id ="birs_client_state">
                                    <?php $this->get_util()->render_html_options($states, $state); ?>
                                </select>
                                <input type="text" name="birs_client_province" id="birs_client_province" value="<?php echo esc_attr($state); ?>" style="display: none;">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Country', 'birchschedule'); ?></label></th>
                            <td>
                                <select name="birs_client_country" id="birs_client_country">
                                    <?php $this->get_util()->render_html_options($countries, $country, 'US'); ?>
                                </select>
                            </td>

                        </tr>
                        <tr>
                            <th><label><?php _e('Zip Code', 'birchschedule'); ?></label></th>
                            <td><input type="text" name="birs_client_zip" id="birs_client_zip" value="<?php echo $zip; ?>"></td>
                        </tr>
                    </table>
                    <div style="height: 30px;">&nbsp;</div>
            </form>
        </div>
        </div>
        <?php
        exit();
    }

    function save_appointment() {
        if (isset($_POST['birs_appointment_id'])) {
            $appointment_id = $_POST['birs_appointment_id'];
        } else {
            $appointment_id = 0;
        }
        check_ajax_referer("birs_save_appointment-$appointment_id");
        $appointment = new BIRS_Appointment($appointment_id, array(
                    'meta_keys' => array(
                        '_birs_appointment_service', '_birs_appointment_staff',
                        '_birs_appointment_location', '_birs_appointment_price',
                        '_birs_appointment_timestamp', '_birs_appointment_notes',
                        '_birs_appointment_duration', '_birs_appointment_client'
                    ),
                    'base_keys' => array(
                        'post_title'
                    )
                ));
        $appointment->copyFromRequest($_POST);
        $client_id = $this->save_client();
        $appointment['_birs_appointment_client'] = $client_id;
        $appointment->save();
    }

    function ajax_save_appointment() {
        $errors = $this->validateData();
        if (!$errors) {
            $this->save_appointment();
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
        wp_delete_post($appointment_id);
        die;
    }

    function save_client() {
        $client = get_posts(array('post_type' => 'birs_client',
            'meta_query' => array(
                array('key' => '_birs_client_email',
                    'value' => $_POST['birs_client_email'])
                )));
        $client_id = 0;
        $client_title = $_POST['birs_client_name_first'] . ' ' . $_POST['birs_client_name_last'];
        if (sizeof($client) > 0) {
            $client_id = $client[0]->ID;
        } else {
            $client = array('post_type' => 'birs_client',
                'post_status' => 'publish',
                'post_title' => $client_title);
            $client_id = wp_insert_post($client);
        }

        if ($client_id) {
            wp_update_post(array('ID' => $client_id,
                'post_title' => $client_title));
            $this->save_field_string($client_id, 'birs_client_name_first');
            $this->save_field_string($client_id, 'birs_client_name_last');
            $this->save_field_string($client_id, 'birs_client_title');
            $this->save_field_string($client_id, 'birs_client_email');
            $this->save_field_string($client_id, 'birs_client_phone');
            $this->save_field_string($client_id, 'birs_client_address1');
            $this->save_field_string($client_id, 'birs_client_address2');
            $this->save_field_string($client_id, 'birs_client_city');
            $this->save_field_string($client_id, 'birs_client_state');
            $this->save_field_string($client_id, 'birs_client_zip');
        }
        return $client_id;
    }

    function validateData() {
        $errors = array();
        $staff_text = 'service provider';
        if ($_POST['action'] == 'birs_save_appointment') {
            $staff_text = 'staff';
        }
        if (!isset($_POST['birs_appointment_staff']) || !isset($_POST['birs_appointment_service']) || !$_POST['birs_appointment_service'] || !$_POST['birs_appointment_staff']) {
            $errors['birs_appointment_service'] = __('Please select a service and a ' . $staff_text, 'birchschedule');
        }
        if (!isset($_POST['birs_appointment_date']) || !$_POST['birs_appointment_date'] || !isset($_POST['birs_appointment_time']) || !$_POST['birs_appointment_time']) {
            $errors['birs_appointment_datetime'] = __('Date & time is required', 'birchschedule');
        } else {
            $datetime = array(
                'date' => $_POST['birs_appointment_date'],
                'time' => $_POST['birs_appointment_time']
            );
            $datetime = $this->get_util()->get_wp_datetime($datetime);
            if (!$datetime) {
                $errors['birs_appointment_datetime'] = __('Date & time is incorrect', 'birchschedule');
            } else {
                $timestamp = $datetime->format('U');
                $_POST['birs_appointment_timestamp'] = $timestamp;
            }
        }

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

    function get_service_length($service_id) {
        $length = get_post_meta($service_id, '_birs_service_length', true);
        $length_type = get_post_meta($service_id, '_birs_service_length_type', true);
        if ($length_type == 'hours') {
            $length = $length * 60;
        }
        $padding = get_post_meta($service_id, '_birs_service_padding', true);
        $padding_type = get_post_meta($service_id, '_birs_service_padding_type', true);
        if ($padding_type == 'before-and-after') {
            $padding *= 2;
        }
        return $length + $padding;
    }

    function render_service_options($staff_id, $selection) {
        $assigned_services = get_post_meta($staff_id, '_birs_assigned_services', true);
        $assigned_services = unserialize($assigned_services);
        $assigned_services = $assigned_services ? $assigned_services : array();
        $all_services = get_posts(array('post_type' => 'birs_service'));
        foreach ($all_services as $service) {
            if ($service->ID == $selection) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            if (array_key_exists($service->ID, $assigned_services)) {
                echo "<option value='$service->ID' $selected>$service->post_title</option>";
            }
        }
    }

    function render_time_options($selection) {
        $options = $this->get_util()->get_time_options();
        foreach ($options as $val => $text) {
            if ($selection == $val) {
                $selected = ' selected="selected" ';
            } else {
                $selected = '';
            }
            echo "<option value='$val' $selected>$text</option>";
        }
    }

    function render_staff_options($staff_id) {
        $staff = get_post($staff_id);
        echo "<option value='$staff->ID'>$staff->post_title</option>";
    }

}
?>
