<?php

final class Birchschedule_View_Calendar_Imp {

    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->view->calendar;
    }

    static function init() {
        add_action( 'admin_init', array( self::$package, 'wp_admin_init' ) );

        self::$birchschedule->view->register_script_data_fn(
            'birchschedule_view_calendar', 'birchschedule_view_calendar',
            array( self::$package, 'get_script_data_fn_view_calendar' ) );
    }

    static function wp_admin_init() {
        global $birchschedule;

        $package = $birchschedule->view->calendar;

        add_action( 'admin_enqueue_scripts', array( __CLASS__, '_enqueue_scripts' ) );

        add_action( 'wp_ajax_birchschedule_view_calendar_query_appointments',
            array( $package, 'ajax_query_appointments' ) );

    }

    static function get_script_data_fn_view_calendar() {
        return array(
            'default_calendar_view' => self::$package->get_default_view(),
            'location_map' => self::$package->get_locations_map(),
            'location_staff_map' => self::$package->get_locations_staff_map(),
            'location_order' => self::$package->get_locations_listing_order(),
            'staff_order' => self::$package->get_staff_listing_order(),
            'slot_minutes' => 15,
            'first_hour' => 9
        );
    }

    static function _enqueue_scripts( $hook ) {
        global $birchschedule;

        if ( $birchschedule->view->get_page_hook( 'calendar' ) !== $hook ) {
            return;
        }

        $birchschedule->view->calendar->enqueue_scripts();
    }

    static function enqueue_scripts() {
        global $birchschedule;

        $birchschedule->view->register_3rd_scripts();
        $birchschedule->view->register_3rd_styles();
        $birchschedule->view->enqueue_scripts(
            array(
                'birchschedule_view_calendar', 'birchschedule_view',
                'birchschedule_view_admincommon', 'birchschedule_model',
                'bootstrap'
            )
        );
        $birchschedule->view->enqueue_styles(
            array(
                'fullcalendar_birchpress', 'bootstrap-theme',
                'birchschedule_admincommon', 'birchschedule_calendar',
                'select2', 'jgrowl'
            )
        );
    }

    static function get_default_view() {
        return 'agendaWeek';
    }

    static function query_appointments( $start, $end, $location_id, $staff_id ) {
        global $birchschedule, $birchpress;

        $criteria = array(
            'start' => $start,
            'end' => $end,
            'location_id' => $location_id,
            'staff_id' => $staff_id
        );
        $appointments =
            $birchschedule->model->booking->query_appointments( $criteria,
            array(
                'appointment_keys' => array(
                    '_birs_appointment_duration', '_birs_appointment_price',
                    '_birs_appointment_timestamp', '_birs_appointment_service'
                ),
                'client_keys' => array( 'post_title' )
            )
        );
        $apmts = array();
        foreach ( $appointments as $appointment ) {
            $title = $birchschedule->model->booking->get_appointment_title( $appointment );
            $appointment['post_title'] = $title;
            $duration = intval( $appointment['_birs_appointment_duration'] );
            $price = $appointment['_birs_appointment_price'];
            $time_start = $appointment['_birs_appointment_timestamp'];
            $time_end = $time_start + $duration * 60;
            $time_start = $birchpress->util->get_wp_datetime( $time_start )->format( 'c' );
            $time_end = $birchpress->util->get_wp_datetime( $time_end )->format( 'c' );
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

        $i18n_msgs = $birchschedule->view->get_frontend_i18n_messages();
        $locations_map = $birchschedule->model->get_locations_map();
        $locations_map[-1] = array(
            'post_title' => $i18n_msgs['All Locations']
        );
        return $locations_map;
    }

    static function get_locations_staff_map() {
        global $birchschedule;

        $i18n_msgs = $birchschedule->view->get_frontend_i18n_messages();
        $map = $birchschedule->model->get_locations_staff_map();
        $allstaff = $birchschedule->model->query(
            array(
                'post_type' => 'birs_staff'
            ),
            array(
                'meta_keys' => array(),
                'base_keys' => array( 'post_title' )
            )
        );
        $new_allstaff = array(
            '-1' => $i18n_msgs['All Providers']
        );
        foreach ( $allstaff as $staff_id => $staff ) {
            $new_allstaff[$staff_id] = $staff['post_title'];
        }
        $map[-1] = $new_allstaff;
        return $map;
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

        $locations = $birchschedule->model->get_locations_listing_order();
        $locations = array_merge( array( -1 ), $locations );
        return $locations;
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
        $start = $birchpress->util->get_wp_datetime( $start )->format( 'U' );
        $end = $_GET['birs_time_end'];
        $end = $birchpress->util->get_wp_datetime( $end )->format( 'U' );
        $location_id = $_GET['birs_location_id'];
        $staff_id = $_GET['birs_staff_id'];

        $apmts = $birchschedule->view->calendar->query_appointments( $start, $end, $location_id, $staff_id );
?>
        <div id="birs_response">
            <?php
        echo json_encode( $apmts );
?>
        </div>
        <?php
        exit;
    }

    static function render_admin_page() {
        global $birchschedule;

        $birchschedule->view->show_notice();
        self::$package->render_calendar_scene();
    }

    static function render_calendar_scene() {
?>
        <div class="birs_scene" id="birs_calendar_scene">
            <h2 style="display:none;"></h2>
            <nav class="navbar navbar-default" role="navigation">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#birs_calendar_menu">
                      <span class="sr-only">Toggle navigation</span>
                      <span class="icon-bar"></span>
                      <span class="icon-bar"></span>
                      <span class="icon-bar"></span>
                    </button>
                </div>
                <div class="collapse navbar-collapse" id="birs_calendar_menu">
                    <div class="btn-group navbar-btn">
                        <button type="button" class="btn btn-default"
                            id="birs_add_appointment">
                            <?php _e( 'New Appointment', 'birchschedule' ); ?>
                        </button>
                        <button type="button" class="btn btn-default"
                            id="birs_calendar_refresh">
                               <span class="glyphicon glyphicon-refresh"></span>
                        </button>
                    </div>
                    <div class="btn-group navbar-btn">
                        <button type="button" class="btn btn-default"
                            id="birs_calendar_today">
                            <?php _e( 'Today', 'birchschedule' ); ?>
                        </button>
                    </div>
                    <div class="btn-group navbar-btn" data-toggle="buttons">
                        <label class="btn btn-default">
                            <input type="radio" name="birs_calendar_view_choice" value="month">
                            <?php _e( 'Month', 'birchschedule' ); ?>
                        </label>
                        <label class="btn btn-default">
                            <input type="radio" name="birs_calendar_view_choice" value="agendaWeek">
                            <?php _e( 'Week', 'birchschedule' ); ?>
                        </label>
                        <label class="btn btn-default">
                            <input type="radio" name="birs_calendar_view_choice" value="agendaDay">
                            <?php _e( 'Day', 'birchschedule' ); ?>
                        </label>
                        <input type="hidden" name="birs_calendar_view" />
                        <input type="hidden" name="birs_calendar_current_date" />
                    </div>
                    <div class="input-group">
                        <select id="birs_calendar_location">
                        </select>
                        <select id="birs_calendar_staff">
                        </select>
                    </div>
                </div>
            </nav>
            <div id="birs_calendar_header">
                <table class="fc-header" style="width:100%">
                    <tbody>
                        <tr>
                            <td class="fc-header-left">
                                <button type="button" class="btn btn-default btn-sm">
                                    <span class="glyphicon glyphicon-chevron-left"></span>
                                </button>
                            </td>
                            <td class="fc-header-center">
                                <span class="fc-header-title">
                                    <h2></h2>
                                </span>
                            </td>
                            <td class="fc-header-right">
                                <button type="button" class="btn btn-default btn-sm">
                                    <span class="glyphicon glyphicon-chevron-right"></span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="birs_calendar">
            </div>
        </div>
        <?php
    }


}

Birchschedule_View_Calendar_Imp::init_vars();

?>
