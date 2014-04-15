<?php

final class Birchschedule_View_Appointments_Imp {

    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->view->appointments;
    }

    static function init() {
        add_action('admin_init', array(self::$package, 'wp_admin_init'));
        add_action('init', array(self::$package, 'wp_init'));
    }

    static function wp_init() {
        self::register_post_type();
    }

    static function wp_admin_init() {
        add_filter('post_updated_messages', 
            array(self::$package, 'get_updated_messages'));
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
            'show_ui' => true,
            'capability_type' => 'birs_appointment',
            'map_meta_cap' => true,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_in_menu' => 'birchschedule_schedule',
            'hierarchical' => false,
            'show_in_nav_menus' => false,
            'rewrite' => false,
            'query_var' => true,
            'supports' => array(''),
            'has_archive' => false
            )
        );
    }

    static function get_updated_messages($messages) {
        global $post, $post_ID;

        $messages['birs_appointment'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __('Appointment updated.', 'birchschedule'),
            2 => __('Custom field updated.', 'birchschedule'),
            3 => __('Custom field deleted.', 'birchschedule'),
            4 => __('Appointment updated.', 'birchschedule'),
            5 => isset($_GET['revision']) ? sprintf(__('Appointment restored to revision from %s', 'birchschedule'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6 => __('Appointment updated.', 'birchschedule'),
            7 => __('Appointment saved.', 'birchschedule'),
            8 => __('Appointment submitted.', 'birchschedule'),
            9 => sprintf(__('Appointment scheduled for: <strong>%1$s</strong>.', 'birchschedule'), date_i18n(__('M j, Y @ G:i', 'birchschedule'), strtotime($post->post_date))),
            10 => __('Appointment draft updated.', 'birchschedule')
        );

        return $messages;
    }

}

Birchschedule_View_Appointments_Imp::init_vars();

?>