<?php

class BIRS_Calendar_View extends BIRS_Admin_View {

    function __construct() {
        parent::__construct();
    }

    function admin_init() {
        parent::admin_init();
        add_action('wp_ajax_birs_query_appointments', array(&$this, 'ajax_query_appointments'));
        add_action('wp_ajax_birs_render_edit_form', array(&$this, 'ajax_render_edit_form'));
        add_action('wp_ajax_birs_save_appointment', array(&$this, 'ajax_save_appointment'));
        add_action('wp_ajax_birs_delete_appointment', array(&$this, 'ajax_delete_appointment'));
        add_filter('birchschedule_general_client_section_admin_edit', array($this, 'get_general_client_section_html'), 10, 2);
        add_filter('birchschedule_client_details_admin_edit', array($this, 'get_client_details_html'), 10, 2);
        add_filter('birchschedule_appointment_details_admin_edit', array($this, 'get_appointment_details_html'), 10, 2);
        add_filter('birchschedule_validate_appointment_info', array($this, 'validate_data'));
        add_filter('birchschedule_validate_booking_form_info', array($this, 'validate_data'));
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
        $params = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'default_calendar_view' => apply_filters('birchschedule_default_calendar_view', 'agendaWeek'),
            'add_appointment_title' => $add_appointment_title,
            'edit_appointment_title' => $edit_appointment_title,
            'gmt_offset' => $gmt_offset,
            'location_staff_map' => $this->get_location_staff_map()
        );
        $scripts[] = array('birs_admin_calendar', 'birs_calendar_params', $params);
        $appointment_edit_params = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'service_staff_map' => $this->get_service_staff_map(),
            'service_price_map' => $this->get_service_price_map(),
            'location_staff_map' => $this->get_location_staff_map()
        );
        $scripts[] = array('birs_admin_appointment_edit', 'birs_appointment_edit_params', $appointment_edit_params);
        return $scripts;
    }

    function get_admin_styles() {
        return array('jquery-ui-bootstrap', 'birs_lib_fullcalendar', 'birchschedule_admin_styles', 'select2', 'jgrowl');
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

    function get_location_staff_map() {
        $map = array();
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_location'
                        ),
                        array()
        );
        $locations = $query->query();
        foreach ($locations as $location) {
            $map[$location->ID] = $location->get_assigned_staff();
        }
        return apply_filters('birchschedule_location_staff_map', $map);
    }

    function get_service_staff_map() {
        $map = array();
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_service'
                        ),
                        array(
                            'meta_keys' => array(
                                '_birs_assigned_staff'
                            )
                        )
        );
        $services = $query->query();
        foreach ($services as $service) {
            $assigned_staff_ids = $service->get_assigned_staff_ids();
            $query = new BIRS_Model_Query(
                            array(
                                'post_type' => 'birs_staff'
                            ),
                            array(
                                'base_keys' => array(
                                    'post_title'
                                )
                            )
            );
            $staff = $query->query();
            $assigned_staff = array();
            foreach ($staff as $thestaff) {
                if (array_key_exists($thestaff->ID, $assigned_staff_ids)) {
                    $assigned_staff[$thestaff->ID] = $thestaff->post_title;
                }
                $map[$service->ID] = $assigned_staff;
            }
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
        $meta_query = array(
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
                'type' => 'UNSIGNED')
        );
        if ($staff_id) {
            $meta_query[] = array('key' => '_birs_appointment_staff',
                'value' => $staff_id,
                'type' => 'UNSIGNED');
        }
        $appointments = get_posts(
                array(
                    'post_type' => 'birs_appointment',
                    'nopaging' => true,
                    'meta_query' => $meta_query
                )
        );
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
        $apmts = apply_filters('birchschedule_query_appointments', $apmts)
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
        ?>
        <div class="birchschedule wrap">
            <div id="birs_calendar_toolbar">
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
                <div id="birs_calendar_filter">
                    <label><?php _e('Location', 'birchschedule'); ?></label>
                    <select id="birs_calendar_location">
                        <?php $this->render_location_options(); ?>
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
        <table>
            <tr>
                <th>
                    <label><?php _e('Title', 'birchschedule'); ?></label>
                </th>
                <td>
                    <select name="birs_client_title" id="birs_client_title">
                        <?php $this->get_util()->render_html_options($client_titles, $client_title); ?>
                    </select>
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_title" />
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Address', 'birchschedule'); ?></label></th>
                <td>
                    <input type="text" name="birs_client_address1" id="birs_client_address1" value="<?php echo $address1; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_address1" />
                </td>
            </tr>
            <tr>
                <th><label></label></th>
                <td>
                    <input type="text" name="birs_client_address2" id="birs_client_address2" value="<?php echo $address2; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_address2" />
                </td>
            </tr>
            <tr>
                <th><label><?php _e('City', 'birchschedule') ?></label></th>
                <td>
                    <input type="text" name="birs_client_city" id="birs_client_city" value="<?php echo $city; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_city" />
                </td>
            </tr>
            <tr>
                <th><label><?php _e('State/Province', 'birchschedule') ?></label></th>
                <td>
                    <select name="birs_client_state" id ="birs_client_state">
                        <?php $this->get_util()->render_html_options($states, $state); ?>
                    </select>
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_state" />
                    <input type="text" name="birs_client_province" id="birs_client_province" value="<?php echo esc_attr($province); ?>" style="display: none;">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_province" />
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Country', 'birchschedule'); ?></label></th>
                <td>
                    <select name="birs_client_country" id="birs_client_country">
                        <?php
                        $this->get_util()->render_html_options($countries, $country, $default_country);
                        ?>
                    </select>
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_country" />
                </td>

            </tr>
            <tr>
                <th><label><?php _e('Zip Code', 'birchschedule'); ?></label></th>
                <td>
                    <input type="text" name="birs_client_zip" id="birs_client_zip" value="<?php echo $zip; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_zip" />
                </td>
            </tr>
        </table>
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
        <table>
            <tr>
                <th>
                    <label><?php _e('Notes', 'birchschedule');
        ?></label>
                </th>
                <td>
                    <textarea id="birs_appointment_notes" name="birs_appointment_notes"><?php echo $notes; ?></textarea>
                    <input type="hidden" name="birs_appointment_fields[]" value="_birs_appointment_notes" />
                </td>
            </tr>
        </table>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    function ajax_render_edit_form() {
        if (isset($_REQUEST['birs_appointment_location'])) {
            $location_id = $_REQUEST['birs_appointment_location'];
        } else {
            $location_id = 0;
        }
        $appointment_id = 0;
        $timestamp = time();
        $price = 0;
        $service_id = 0;
        if (isset($_REQUEST['birs_appointment_staff'])) {
            $staff_id = $_REQUEST['birs_appointment_staff'];
        } else {
            $staff_id = 0;
        }
        $date = '';
        $time = 540;
        $client_id = 0;
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
        }
        ?>
        <div id="birs_appointment_edit">
            <ul>
                <li><a href="#birs_appointment_info"><?php _e('General Info', 'birchschedule'); ?></a></li>
                <li><a href="#birs_appointment_details"><?php _e('Appointment Details', 'birchschedule'); ?></a></li>
                <li><a href="#birs_client_details"><?php _e('Client Details', 'birchschedule'); ?></a></li>
            </ul>
            <form id="birs_appointment_form">
                <div class="wrap" id="birs_appointment_info">
                    <?php wp_nonce_field("birs_save_appointment-$appointment_id"); ?>
                    <?php wp_nonce_field("birs_delete_appointment-$appointment_id", 'birs_delete_appointment_nonce', false); ?>
                    <input type="hidden" name="birs_appointment_id" id="birs_appointment_id" value="<?php echo $appointment_id; ?>">
                    <input type="hidden" name="birs_appointment_location" id="birs_appointment_location" value="<?php echo $location_id; ?>">
                    <div id="birs_general_section_appointment">
                        <table style="margin-left:-12px;">
                            <tr>
                                <th>
                                    <label><?php _e('Service', 'birchschedule'); ?></label>
                                </th>
                                <td>
                                    <select id="birs_appointment_service" name="birs_appointment_service"><?php $service_id = $this->render_service_options($service_id); ?></select>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label><?php _e('Staff', 'birchschedule'); ?></label></th>
                                <td>
                                    <select id="birs_appointment_staff" name="birs_appointment_staff"><?php $this->render_staff_options($location_id, $service_id, $staff_id); ?></select>
                                </td>
                            </tr>
                            <tr class="error">
                                <th></th>
                                <td>
                                    <label id="birs_appointment_service_error" class="error"></label>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label><?php _e('Date & Time', 'birchschedule'); ?></label>
                                </th>
                                <td>
                                    <input id="birs_appointment_date" name="birs_appointment_date" type="text" value="<?php echo $date ?>">
                                    <select id="birs_appointment_time" name="birs_appointment_time">
                                        <?php $this->render_time_options($time); ?>
                                    </select></td>
                            </tr>
                            <tr class="error">
                                <th></th>
                                <td>
                                    <label id="birs_appointment_datetime_error" class="error"></label>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label><?php echo apply_filters('birchschedule_price_label', __('Price', 'birchschedule')); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="birs_appointment_price" name="birs_appointment_price" value="<?php echo $price; ?>">
                                </td>
                            </tr>
                        </table>
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
                    echo apply_filters('birchschedule_client_details_admin_edit', '', $client_id);
                    ?>
                </div>
            </form>
        </div>
        <?php
        exit();
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
        <table>
            <tr>
                <th>
                    <label><?php _e('First Name', 'birchschedule'); ?></label>
                </th>
                <td>
                    <input type="text" id="birs_client_name_first" name="birs_client_name_first" value="<?php echo $first_name; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_first" />
                </td>
            </tr>
            <tr class="error">
                <th></th>
                <td>
                    <label id="birs_client_name_first_error" class="error"></label>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Last Name', 'birchschedule'); ?></label>
                </th>
                <td>
                    <input type="text" id="birs_client_name_last" name="birs_client_name_last" value="<?php echo $last_name; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_last" />
                </td>  
            </tr>
            <tr class="error">
                <th></th>
                <td>
                    <label id="birs_client_name_last_error" class="error"></label>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Email', 'birchschedule'); ?></label>
                </th>
                <td>
                    <input type="text" id="birs_client_email" name="birs_client_email" value="<?php echo $email; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_email" />
                </td>
            </tr>
            <tr class="error">
                <th></th>
                <td>
                    <label id="birs_client_email_error" class="error"></label>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Phone', 'birchschedule'); ?></label>
                </th>
                <td>
                    <input type="text" id="birs_client_phone" name="birs_client_phone" value="<?php echo $phone; ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_phone" />
                </td>
            </tr>
            <tr class="error">
                <th></th>
                <td>
                    <label id="birs_client_phone_error" class="error"></label>
                </td>
            </tr>
        </table>
        <?php
        $html = ob_get_clean();
        return $html;
    }

    function save_appointment() {
        if (isset($_POST['birs_appointment_id'])) {
            $appointment_id = $_POST['birs_appointment_id'];
        } else {
            $appointment_id = 0;
        }
        if (isset($_POST['birs_appointment_fields'])) {
            $fields = $_POST['birs_appointment_fields'];
        } else {
            $fields = array();
        }
        $fields = array_merge($fields, array(
            '_birs_appointment_service', '_birs_appointment_staff',
            '_birs_appointment_location', '_birs_appointment_price',
            '_birs_appointment_timestamp', '_birs_appointment_duration',
            '_birs_appointment_padding_before', '_birs_appointment_padding_after',
            '_birs_appointment_client'
                ));
        $appointment = new BIRS_Appointment($appointment_id, array(
                    'meta_keys' => $fields,
                    'base_keys' => array(
                        'post_title'
                    )
                ));
        $appointment->copyFromRequest($_POST);
        $client_id = $this->save_client();
        $appointment['_birs_appointment_client'] = $client_id;
        return $appointment->save();
    }

    function ajax_save_appointment() {
        if (isset($_POST['birs_appointment_id'])) {
            $appointment_id = $_POST['birs_appointment_id'];
        } else {
            $appointment_id = 0;
        }
        check_ajax_referer("birs_save_appointment-$appointment_id");
        $errors = apply_filters('birchschedule_validate_appointment_info', array());
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
        do_action("birchschedule_appointment_pre_delete", $appointment_id);
        if(wp_delete_post($appointment_id)) {
            do_action("birchschedule_appointment_deleted", $appointment_id);
        }
        die;
    }

    function save_client() {
        if (isset($_POST['birs_client_fields'])) {
            $fields = $_POST['birs_client_fields'];
        } else {
            $fields = array();
        }
        $client = new BIRS_Client(0, array(
                    'meta_keys' => $fields,
                    'base_keys' => array(
                        'post_title'
                    )
                ));
        $client->copyFromRequest($_POST);
        $client->load_id_by_email();
        $client_id = $client->save();

        return $client_id;
    }

    function validate_data() {
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

    function map_service($service) {
        return $service->post_title;
    }

    function render_location_options() {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_location'
                        ),
                        array(
                            'base_keys' => array('post_title')
                        )
        );
        $locations = array_map(array($this, 'map_service'), $query->query());
        $this->get_util()->render_html_options($locations, false);
        return key($locations);
    }

    function render_service_options($selection) {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_service'
                        ),
                        array(
                            'base_keys' => array('post_title')
                        )
        );
        $services = array_map(array($this, 'map_service'), $query->query());
        $this->get_util()->render_html_options($services, $selection);
        if ($selection) {
            return $selection;
        } else {
            return key($services);
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

    function render_staff_options($location, $service, $selection) {
        $locatoin_map = $this->get_location_staff_map();
        if (isset($locatoin_map[$location])) {
            $location_staff = $locatoin_map[$location];
        } else {
            $location_staff = array();
        }
        $service_map = $this->get_service_staff_map();
        if (isset($service_map[$service])) {
            $service_staff = $service_map[$service];
        } else {
            $service_staff = array();
        }
        $staff = array_intersect_assoc($service_staff, $location_staff);
        $this->get_util()->render_html_options($staff, $selection);
    }

}
?>