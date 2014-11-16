<?php

final class Birchschedule_View_Imp {

    private static $page_hooks;

    static function init() {
        global $birchschedule;
        $package = $birchschedule->view;

        $birchschedule->add_less_dir( $birchschedule->plugin_dir_path() . 'assets/css' );

        add_action( 'init', array( $package, 'wp_init' ) );

        add_action( 'admin_init', array( $package, 'wp_admin_init' ) );

        add_action( 'admin_menu', array( $package, 'create_admin_menus' ) );

        add_action( 'custom_menu_order', array( $package, 'if_change_custom_menu_order' ), 100 );

        add_action( 'menu_order', array( $package, 'change_admin_menu_order' ), 100 );

        add_action( 'plugins_loaded', array( $package, 'load_i18n' ) );

        $post_types = array(
            'birs_appointment', 'birs_client',
            'birs_location', 'birs_staff',
            'birs_service'
        );
        foreach ( $post_types as $post_type ) {
            add_action( 'birchpress_view_load_post_edit_' . $post_type . '_after',
                array( $package, 'on_load_post_edit' ) );

            add_action( 'birchpress_view_load_post_new_' . $post_type . '_after',
                array( $package, 'on_load_post_new' ) );

            add_action( 'birchpress_view_enqueue_scripts_post_new_' . $post_type . '_after',
                array( $package, 'on_enqueue_scripts_post_new' ) );

            add_action( 'birchpress_view_enqueue_scripts_post_edit_' . $post_type . '_after',
                array( $package, 'on_enqueue_scripts_post_edit' ) );

            add_action( 'birchpress_view_enqueue_scripts_post_list_' . $post_type . '_after',
                array( $package, 'on_enqueue_scripts_post_list' ) );

            add_action( 'birchpress_view_save_post_' . $post_type . '_after',
                array( $package, 'on_save_post' ) );

            add_filter( 'birchpress_view_pre_save_post_'. $post_type,
                array( $package, 'apply_pre_save_post' ), 20, 3 );
        }

        self::init_page_hooks();
    }

    static function init_doing_ajax() {

    }

    static function wp_init() {
        global $birchschedule;
        $package = $birchschedule->view;

        if ( !defined( 'DOING_AJAX' ) ) {
            $package->register_common_scripts();
            $package->register_common_styles();
            $package->register_common_scripts_data_fns();
        }

    }

    static function wp_admin_init() {
    }

    static function init_page_hooks() {
        self::$page_hooks = array();
    }

    static function get_current_post_type() {
        global $birchpress;

        return $birchpress->view->get_current_post_type();
    }

    static function on_load_post_edit( $arg ) {
        global $birchschedule;

        $birchschedule->view->load_page_edit( $arg );
        $birchschedule->view->load_post_edit( $arg );
    }

    static function on_load_post_new( $arg ) {
        global $birchschedule;

        $birchschedule->view->load_page_edit( $arg );
        $birchschedule->view->load_post_new( $arg );
    }

    static function load_page_edit( $arg ) {}

    static function load_post_new( $arg ) {}

    static function load_post_edit( $arg ) {}

    static function on_enqueue_scripts_post_new( $arg ) {
        global $birchschedule;

        $birchschedule->view->enqueue_scripts_post_new( $arg );
        $birchschedule->view->enqueue_scripts_edit( $arg );
    }

    static function on_enqueue_scripts_post_edit( $arg ) {
        global $birchschedule;

        $birchschedule->view->enqueue_scripts_post_edit( $arg );
        $birchschedule->view->enqueue_scripts_edit( $arg );
    }

    static function on_enqueue_scripts_post_list( $arg ) {
        global $birchschedule;

        $birchschedule->view->enqueue_scripts_list( $arg );
    }

    static function save_post( $post_a ) {}

    static function on_save_post( $post_a ) {
        global $birchschedule;

        $birchschedule->view->save_post( $post_a );
    }

    static function pre_save_post( $post_data, $post_attr ) { return $post_data; }

    static function apply_pre_save_post( $post_data, $post_data2, $post_attr ) {
        global $birchschedule;

        return $birchschedule->view->pre_save_post( $post_data, $post_attr );
    }

    static function enqueue_scripts_post_new( $arg ) {}

    static function enqueue_scripts_post_edit( $arg ) {}

    static function enqueue_scripts_edit( $arg ) {}

    static function enqueue_scripts_list( $arg ) {}

    static function enqueue_scripts( $scripts ) {
        global $birchpress;

        $birchpress->view->enqueue_scripts( $scripts );
    }

    static function enqueue_styles( $styles ) {
        global $birchpress;

        $birchpress->view->enqueue_styles( $styles );

    }

    static function merge_request( $model, $config, $request ) {
        global $birchschedule;

        return $birchschedule->model->merge_data( $model, $config, $request );
    }

    static function apply_currency_to_label( $label, $currency_code ) {
        global $birchpress, $birchschedule;

        $currencies = $birchpress->util->get_currencies();
        $currency = $currencies[$currency_code];
        $symbol = $currency['symbol_right'];
        if ( $symbol == '' ) {
            $symbol = $currency['symbol_left'];
        }
        return $label = $label . ' (' . $symbol . ')';
    }

    static function render_errors() {
        global $birchschedule;

        $errors = $birchschedule->view->get_errors();
        if ( $errors && sizeof( $errors ) > 0 ) {
            echo '<div id="birchschedule_errors" class="error fade">';
            foreach ( $errors as $error ) {
                echo '<p>' . $error . '</p>';
            }
            echo '</div>';
            update_option( 'birchschedule_errors', '' );
        }
    }

    static function get_errors() {
        return get_option( 'birchschedule_errors' );
    }

    static function has_errors() {
        global $birchschedule;

        $errors = $birchschedule->view->get_errors();
        if ( $errors && sizeof( $errors ) > 0 ) {
            return true;
        } else {
            return false;
        }
    }

    static function save_errors( $errors ) {
        update_option( 'birchschedule_errors', $errors );
    }

    static function get_screen( $hook_name ) {
        global $birchpress;

        return $birchpress->view->get_screen( $hook_name );
    }

    static function show_notice() {}

    static function add_page_hook( $key, $hook ) {
        self::$page_hooks[$key] = $hook;
    }

    static function get_page_hook( $key ) {
        if ( isset( self::$page_hooks[$key] ) ) {
            return self::$page_hooks[$key];
        } else {
            return '';
        }
    }

    static function get_custom_code_css( $shortcode ) {
        return '';
    }

    static function get_shortcodes() {
        return array();
    }

    static function get_languages_dir() {
        return 'birchschedule/languages';
    }

    static function load_i18n() {
        global $birchschedule;

        $package = $birchschedule->view;
        $lan_dir = $package->get_languages_dir();
        load_plugin_textdomain( 'birchschedule', false, $lan_dir );
    }

    static function create_admin_menus() {
        global $menu, $birchschedule;

        $package = $birchschedule->view;
        $package->create_menu_scheduler();
        $package->reorder_submenus();
    }

    static function if_change_custom_menu_order() {
        return true;
    }

    static function change_admin_menu_order( $menu_order ) {

        $custom_menu_order = array();

        $client_menu = array_search( 'edit.php?post_type=birs_client', $menu_order );

        foreach ( $menu_order as $index => $item ) {

            if ( ( ( 'edit.php?post_type=birs_appointment' ) == $item ) ) {
                $custom_menu_order[] = $item;
                $custom_menu_order[] = 'edit.php?post_type=birs_client';
                unset( $menu_order[$client_menu] );
            } else {
                if ( 'edit.php?post_type=birs_client' != $item )
                    $custom_menu_order[] = $item;
            }
        }

        return $custom_menu_order;
    }

    static function create_menu_scheduler() {
        global $birchschedule;
        $package = $birchschedule->view;

        $page_hook_calendar =
            add_submenu_page( 'edit.php?post_type=birs_appointment', __( 'Calendar', 'birchschedule' ),
            __( 'Calendar', 'birchschedule' ), 'edit_birs_appointments', 'birchschedule_calendar',
            array( $package, 'render_calendar_page' ) );
        $package->add_page_hook( 'calendar', $page_hook_calendar );

        $page_hook_settings =
            add_submenu_page( 'edit.php?post_type=birs_appointment',
            __( 'BirchPress Scheduler Settings', 'birchschedule' ),
            __( 'Settings', 'birchschedule' ), 'manage_birs_settings',
            'birchschedule_settings', array( $package, 'render_settings_page' ) );
        $package->add_page_hook( 'settings', $page_hook_settings );

        $page_hook_help = add_submenu_page( 'edit.php?post_type=birs_appointment',
            __( 'Help', 'birchschedule' ), __( 'Help', 'birchschedule' ),
            'read', 'birchschedule_help', array( $package, 'render_help_page' ) );
        $package->add_page_hook( 'help', $page_hook_help );
    }

    static function reorder_submenus() {
        global $submenu, $birchschedule;

        $package = $birchschedule->view;
        $sub_items = &$submenu['edit.php?post_type=birs_appointment'];
        $location = $package->get_submenu( $sub_items, 'location' );
        $staff = $package->get_submenu( $sub_items, 'staff' );
        $service = $package->get_submenu( $sub_items, 'service' );
        $settings = $package->get_submenu( $sub_items, 'settings' );
        $help = $package->get_submenu( $sub_items, 'help' );
        $calendar = $package->get_submenu( $sub_items, 'calendar' );
        $new_appointment = $package->get_submenu( $sub_items, 'post-new.php?post_type=birs_appointment' );

        $sub_items = array(
            $calendar,
            $new_appointment,
            $location,
            $staff,
            $service,
            $settings,
            $help
        );
    }

    static function get_submenu( $submenus, $name ) {
        foreach ( $submenus as $submenu ) {
            $pos = strpos( $submenu[2], $name );
            if ( $pos || $pos === 0 ) {
                return $submenu;
            }
        }
        return false;
    }

    static function register_script_data_fn( $handle, $data_name, $fn ) {
        global $birchpress;

        $birchpress->view->register_script_data_fn( $handle, $data_name, $fn );
    }

    static function get_admin_i18n_messages() {
        global $birchschedule;
        return $birchschedule->view->get_frontend_i18n_messages();
    }

    static function get_frontend_i18n_messages() {
        return array(
            'Loading...' => __( 'Loading...', 'birchschedule' ),
            'Loading appointments...' => __( 'Loading appointments...', 'birchschedule' ),
            'Saving...' => __( 'Saving...', 'birchschedule' ),
            'Save' => __( 'Save', 'birchschedule' ),
            'Please wait...' => __( 'Please wait...', 'birchschedule' ),
            'Schedule' => __( 'Schedule', 'birchschedule' ),
            'Are you sure you want to cancel this appointment?' => __( 'Are you sure you want to cancel this appointment?', 'birchschedule' ),
            'Your appointment has been cancelled successfully.' => __( 'Your appointment has been cancelled successfully.', 'birchschedule' ),
            "The appointment doesn't exist or has been cancelled." => __( "The appointment doesn't exist or has been cancelled.", 'birchschedule' ),
            'Your appointment has been rescheduled successfully.' => __( 'Your appointment has been rescheduled successfully.', 'birchschedule' ),
            'Your appointment can not be cancelled now according to our booking policies.' => __( 'Your appointment can not be cancelled now according to our booking policies.', 'birchschedule' ),
            'Your appointment can not be rescheduled now according to our booking policies.' => __( 'Your appointment can not be rescheduled now according to our booking policies.', 'birchschedule' ),
            'There are no available times.' => __( 'There are no available times.', 'birchschedule' ),
            '(Deposit)' => __( '(Deposit)', 'birchschedule' ),
            'Reschedule' => __( 'Reschedule', 'birchschedule' ),
            'Change' => __( 'Change', 'birchschedule' ),
            'No Preference' => __( 'No Preference', 'birchschedule' ),
            'All Locations' => __( 'All Locations', 'birchschedule' ),
            'All Providers' => __( 'All Providers', 'birchschedule' )
        );
    }

    static function render_ajax_success_message( $success ) {
        global $birchpress;

        $birchpress->view->render_ajax_success_message( $success );
    }

    static function render_ajax_error_messages( $errors ) {
        global $birchpress;

        $birchpress->view->render_ajax_error_messages( $errors );
    }

    static function get_query_array( $query, $keys ) {
        global $birchpress;
        return $birchpress->view->get_query_array( $query, $keys );
    }

    static function get_query_string( $query, $keys ) {
        global $birchpress;

        return $birchpress->view->get_query_string( $query, $keys );
    }

    static function get_script_data_fn_model() {
        global $birchschedule, $birchpress;
        return array(
            'admin_url' => admin_url(),
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'all_schedule' => $birchschedule->model->schedule->get_all_calculated_schedule(),
            'all_daysoff' => $birchschedule->model->get_all_daysoff(),
            'gmt_offset' => $birchpress->util->get_gmt_offset(),
            'future_time' => $birchschedule->model->get_future_time(),
            'cut_off_time' => $birchschedule->model->get_cut_off_time(),
            'fully_booked_days' => $birchschedule->model->schedule->get_fully_booked_days()
        );
    }

    static function get_script_data_fn_view() {
        global $birchschedule, $birchpress;

        $package = $birchschedule->view;
        return array(
            'datepicker_i18n_options' => $birchpress->util->get_datepicker_i18n_params(),
            'fc_i18n_options' => $birchpress->util->get_fullcalendar_i18n_params(),
            'i18n_messages' => $package->get_frontend_i18n_messages(),
            'i18n_countries' => $birchpress->util->get_countries(),
            'i18n_states' => $birchpress->util->get_states(),
            'plugin_url' => $birchschedule->plugin_url()
        );
    }

    static function get_script_data_fn_admincommon() {
        global $birchschedule, $birchpress;

        $package = $birchschedule->view;
        return array(
            'i18n_messages' => $package->get_admin_i18n_messages()
        );
    }

    static function register_common_scripts_data_fns() {
        global $birchschedule, $birchpress;

        $package = $birchschedule->view;

        $package->register_script_data_fn( 'birchschedule_model', 'birchschedule_model',
            array( $package, 'get_script_data_fn_model' ) );
        $package->register_script_data_fn( 'birchschedule_view', 'birchschedule_view',
            array( $package, 'get_script_data_fn_view' ) );
        $package->register_script_data_fn( 'birchschedule_view_admincommon', 'birchschedule_view_admincommon',
            array( $package, 'get_script_data_fn_admincommon' ) );
    }

    static function register_3rd_scripts() {
        global $birchpress, $birchschedule;
        $version = $birchschedule->product_version;

        $birchpress->view->register_3rd_scripts();
        wp_register_script( 'moment',
            $birchschedule->plugin_url() . '/lib/assets/js/moment/moment.min.js',
            array(), '1.7.0' );

        wp_register_script( 'jgrowl',
            $birchschedule->plugin_url() . '/lib/assets/js/jgrowl/jquery.jgrowl.js',
            array( 'jquery' ), '1.4.0' );

        wp_register_script( 'jscolor',
            $birchschedule->plugin_url() . '/lib/assets/js/jscolor/jscolor.js',
            array(), '1.4.0' );

        wp_register_script( 'bootstrap',
            $birchschedule->plugin_url() . '/lib/assets/js/bootstrap/js/bootstrap.js',
            array( 'jquery' ), '3.0.3' );

        wp_deregister_script( 'select2' );
        wp_register_script( 'select2',
            $birchschedule->plugin_url() . '/lib/assets/js/select2/select2.min.js',
            array( 'jquery' ), '3.4.2' );

        wp_register_script( 'fullcalendar_birchpress',
            $birchschedule->plugin_url() . '/lib/assets/js/fullcalendar/fullcalendar_birchpress.js',
            array( 'jquery-ui-draggable', 'jquery-ui-resizable',
                'jquery-ui-dialog', 'jquery-ui-datepicker',
                'jquery-ui-tabs', 'jquery-ui-autocomplete' ), '1.6.4' );

        wp_register_script( 'filedownload_birchpress',
            $birchschedule->plugin_url() . '/lib/assets/js/filedownload/jquery.fileDownload.js',
            array( 'jquery' ), '1.4.0' );
    }

    static function register_3rd_styles() {
        global $birchschedule;
        $version = $birchschedule->product_version;

        wp_register_style( 'fullcalendar_birchpress',
            $birchschedule->plugin_url() . '/lib/assets/js/fullcalendar/fullcalendar.css',
            array(), '1.5.4' );

        wp_register_style( 'jquery-ui-bootstrap',
            $birchschedule->plugin_url() . '/lib/assets/css/jquery-ui-bootstrap/jquery-ui-1.9.2.custom.css',
            array(), '0.22' );
        wp_register_style( 'jquery-ui-no-theme',
            $birchschedule->plugin_url() . '/lib/assets/css/jquery-ui-no-theme/jquery-ui-1.9.2.custom.css',
            array(), '1.9.2' );
        wp_register_style( 'jquery-ui-smoothness',
            $birchschedule->plugin_url() . '/lib/assets/css/jquery-ui-smoothness/jquery-ui-1.9.2.custom.css',
            array(), '1.9.2' );

        wp_register_style( 'bootstrap',
            $birchschedule->plugin_url() . '/lib/assets/js/bootstrap/css/bootstrap.css',
            array(), '3.0.3' );

        wp_register_style( 'bootstrap-theme',
            $birchschedule->plugin_url() . '/lib/assets/js/bootstrap/css/bootstrap-theme.css',
            array( 'bootstrap' ), '3.1.1' );

        wp_deregister_style( 'select2' );
        wp_register_style( 'select2',
            $birchschedule->plugin_url() . '/lib/assets/js/select2/select2.css',
            array(), '3.4.2' );

        wp_register_style( 'jgrowl',
            $birchschedule->plugin_url() . '/lib/assets/js/jgrowl/jquery.jgrowl.css',
            array(), '1.4.0' );
    }

    static function register_common_scripts() {
        global $birchschedule;
        $version = $birchschedule->product_version;

        wp_register_script( 'birchschedule',
            $birchschedule->plugin_url() . '/assets/js/base.js',
            array( 'jquery', 'birchpress', 'birchpress_util' ), "$version" );

        wp_register_script( 'birchschedule_model',
            $birchschedule->plugin_url() . '/assets/js/model/base.js',
            array( 'jquery', 'birchpress', 'birchschedule' ), "$version" );

        wp_register_script( 'birchschedule_view',
            $birchschedule->plugin_url() . '/assets/js/view/base.js',
            array( 'jquery', 'birchpress', 'birchschedule', 'birchschedule_model' ), "$version" );

        wp_register_script( 'birchschedule_view_admincommon',
            $birchschedule->plugin_url() . '/assets/js/view/admincommon/base.js',
            array( 'jquery', 'birchpress', 'birchschedule', 'jgrowl' ), "$version" );

        wp_register_script( 'birchschedule_view_clients_edit',
            $birchschedule->plugin_url() . '/assets/js/view/clients/edit/base.js',
            array( 'birchschedule_view_admincommon', 'birchschedule_view' ), "$version" );

        wp_register_script( 'birchschedule_view_locations_edit',
            $birchschedule->plugin_url() . '/assets/js/view/locations/edit/base.js',
            array( 'birchschedule_view_admincommon', 'birchschedule_view' ), "$version" );

        wp_register_script( 'birchschedule_view_services_edit',
            $birchschedule->plugin_url() . '/assets/js/view/services/edit/base.js',
            array( 'birchschedule_view_admincommon', 'birchschedule_view' ), "$version" );

        wp_register_script( 'birchschedule_view_staff_edit',
            $birchschedule->plugin_url() . '/assets/js/view/staff/edit/base.js',
            array( 'birchschedule_view_admincommon', 'birchschedule_view',
                'jscolor' ), "$version" );

        wp_register_script( 'birchschedule_view_calendar',
            $birchschedule->plugin_url() . '/assets/js/view/calendar/base.js',
            array( 'birchschedule_view_admincommon', 'birchschedule_view',
                'fullcalendar_birchpress', 'moment' ), "$version" );

        wp_register_script( 'birchschedule_view_bookingform',
            $birchschedule->plugin_url() . '/assets/js/view/bookingform/base.js',
            array( 'jquery-ui-datepicker', 'birchschedule_view' ), "$version" );

    }

    static function register_common_styles() {
        global $birchschedule;
        $version = $birchschedule->product_version;

        wp_register_style( 'birchschedule_admincommon',
            $birchschedule->plugin_url() . '/assets/css/admincommon/base.css',
            array( 'jgrowl', 'select2' ), "$version" );

        wp_register_style( 'birchschedule_calendar',
            $birchschedule->plugin_url() . '/assets/css/calendar/base.css',
            array( 'jgrowl', 'bootstrap-theme' ), "$version" );

        wp_register_style( 'birchschedule_appointments_edit',
            $birchschedule->plugin_url() . '/assets/css/appointments/edit/base.css',
            array( 'jquery-ui-no-theme' ), "$version" );

        wp_register_style( 'birchschedule_appointments_new',
            $birchschedule->plugin_url() . '/assets/css/appointments/new/base.css',
            array( 'jquery-ui-no-theme' ), "$version" );

        wp_register_style( 'birchschedule_services_edit',
            $birchschedule->plugin_url() . '/assets/css/services/edit/base.css',
            array(), "$version" );

        wp_register_style( 'birchschedule_staff_edit',
            $birchschedule->plugin_url() . '/assets/css/staff/edit/base.css',
            array(), "$version" );

        wp_register_style( 'birchschedule_locations_edit',
            $birchschedule->plugin_url() . '/assets/css/locations/edit/base.css',
            array(), "$version" );

        wp_register_style( 'birchschedule_bookingform',
            $birchschedule->plugin_url() . '/assets/css/bookingform/base.css',
            array( 'jquery-ui-no-theme' ), "$version" );
    }

}
