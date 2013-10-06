<?php

class Birchschedule_View_Calendar_Imp {

    private function __construct() {
    }

    static function init() {
        global $birchschedule;
        
        $package = $birchschedule->view->calendar;
        $package->add_action('admin_init', 'wp_admin_init');
    }

    static function wp_admin_init() {
        global $birchschedule;
        
        $package = $birchschedule->view->calendar;
        self::register_post_type();
        add_action('admin_enqueue_scripts', array(__CLASS__, '_enqueue_scripts'));
        $package->add_action('wp_ajax_birchschedule_view_calendar_query_appointments', 
            'ajax_query_appointments');
    }

    private static function register_post_type() {
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
    }

    static function _enqueue_scripts($hook) {
        global $birchschedule;

        if($birchschedule->view->get_page_hook('calendar') !== $hook) {
            return;
        }

        $birchschedule->view->calendar->enqueue_scripts();
    }

    static function enqueue_scripts() {
        global $birchschedule;

        $scripts = $birchschedule->view->calendar->get_scripts();
        $styles = $birchschedule->view->calendar->get_styles();

        $birchschedule->view->enqueue_scripts($scripts);
        $birchschedule->view->enqueue_styles($styles);
    }

    static function get_scripts() {
        global $birchschedule, $birchpress;

        $package = $birchschedule->view->calendar;
        $scripts = array();
        $save_button_html = '<input type="button" class="button-primary tips" name="save_appointment" id="save_appointment" value="Save" alt="Save Data" onclick="javascript:void(0)">';
        $delete_button_html = '<a id="delete_appointment" class="submitdelete deletion" href="javascript:void(0)">' . __('Delete', 'birchschedule') . '</a>';

        $add_appointment_title = '<div>' . $save_button_html .
                '<span>' . __('Add Appointment', 'birchschedule') . '</span>' . '</div>';
        $edit_appointment_title = '<div class="submitbox">' .
                $save_button_html .
                $delete_button_html .
                '<span>' . __('Edit Appointment', 'birchschedule') . '</span>' . '</div>';
        $gmt_offset = $birchpress->util->get_gmt_offset();
        $fc_time_format = $birchpress->util->date_time_format_php_to_fullcalendar(get_option('time_format', 'g:i a'));
        $params = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'default_calendar_view' => $package->get_default_view(),
            'add_appointment_title' => $add_appointment_title,
            'edit_appointment_title' => $edit_appointment_title,
            'gmt_offset' => $gmt_offset,
            'fc_time_format' => $fc_time_format,
            'location_map' => $package->get_locations_map(),
            'location_staff_map' => $package->get_locations_staff_map(),
            'location_order' => $package->get_locations_listing_order(),
            'staff_order' => $package->get_staff_listing_order(),
            'fc_i18n_options' => $birchpress->util->get_fullcalendar_i18n_params(),
            'i18n' => array(
                'loading' => __('Loading...', 'birchschedule'),
                'loading_appointments' => __('Loading appointments...', 'birchschedule'),
                'Saving' => __('Saving...', 'birchschedule'),
                'Save' => __('Save', 'birchschedule')
            )
        );
        $scripts[] = array('birs_admin_calendar', 'birs_calendar_params', $params);

        return $scripts;
    }

    static function get_styles() {
        return array('jquery-ui-bootstrap', 'birs_lib_fullcalendar', 
            'birchschedule_admin_styles', 'birchschedule_admin_calendar', 
            'select2', 'jgrowl');
    }

    static function get_default_view() {
        return 'agendaWeek';
    }

    static function query_appointments($start, $end, $location_id, $staff_id) {
        global $birchschedule, $birchpress;

        $appointments = 
            $birchschedule->model->query_appointments($start, $end, $location_id, $staff_id, 
                array(
                    'base_keys' => array(),
                    'meta_keys' => array(
                        '_birs_appointment_duration', '_birs_appointment_price',
                        '_birs_appointment_timestamp'
                    )
                )
            );
        $apmts = array();
        foreach ($appointments as $appointment) {
            $duration = intval($appointment['_birs_appointment_duration']);
            $price = $appointment['_birs_appointment_price'];
            $time_start = $appointment['_birs_appointment_timestamp'];
            $time_end = $time_start + $duration * 60;
            $time_start = $birchpress->util->get_wp_datetime($time_start)->format('c');
            $time_end = $birchpress->util->get_wp_datetime($time_end)->format('c');
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

        return $apmts;
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

    static function ajax_query_appointments() {
        global $birchschedule, $birchpress;

        $start = $_GET['birs_time_start'];
        $start = $birchpress->util->get_wp_datetime($start)->format('U');
        $end = $_GET['birs_time_end'];
        $end = $birchpress->util->get_wp_datetime($end)->format('U');
        $location_id = $_GET['birs_location_id'];
        $staff_id = $_GET['birs_staff_id'];

        $apmts = $birchschedule->view->calendar->
            query_appointments($start, $end, $location_id, $staff_id);
        ?>
        <div id="birs_response">
            <?php
            echo json_encode($apmts);
            ?>
        </div>
        <?php
        exit;
    }

    static function render_admin_page() {
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

}
?>