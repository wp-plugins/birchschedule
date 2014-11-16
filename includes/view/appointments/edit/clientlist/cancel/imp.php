<?php

final class Birchschedule_View_Appointments_Edit_Clientlist_Cancel_Imp {

    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->view->appointments->edit->clientlist->cancel;
    }

    static function init() {
        add_action('admin_init', array(self::$package, 'wp_admin_init'));
        add_action('birchschedule_view_register_common_scripts_after', 
            array(self::$package, 'register_scripts'));
    }

    static function wp_admin_init() {
        add_action('wp_ajax_birchschedule_view_appointments_edit_clientlist_cancel_cancel',
            array(self::$package, 'ajax_cancel'));
        add_action('birchschedule_view_enqueue_scripts_post_edit_birs_appointment_after',
            array(self::$package, 'enqueue_scripts_post_edit_birs_appointment'));
    }

    static function register_scripts() {
        $version = self::$birchschedule->product_version;

        wp_register_script('birchschedule_view_appointments_edit_clientlist_cancel', 
            self::$birchschedule->plugin_url() . '/assets/js/view/appointments/edit/clientlist/cancel/base.js', 
            array('birchschedule_view_admincommon', 'birchschedule_view'), "$version");

    }

    static function enqueue_scripts_post_edit_birs_appointment($arg) {
        birch_assert(is_array($arg) && isset($arg['post_type']) && 
            $arg['post_type'] == 'birs_appointment');

        global $birchschedule;

        $birchschedule->view->register_3rd_scripts();
        $birchschedule->view->register_3rd_styles();
        $birchschedule->view->enqueue_scripts(
            array(
                'birchschedule_view_appointments_edit_clientlist_cancel'
            )
        );
    }

    static function ajax_cancel() {
        $client_id = $_POST['birs_client_id'];
        $appointment_id = $_POST['birs_appointment_id'];
        $appointment1on1 = self::$birchschedule->model->booking->get_appointment1on1(
            $appointment_id, $client_id);
        $success = array(
            'code' => 'reload',
            'message' => ''
        );
        if($appointment1on1) {
            self::$birchschedule->model->booking->cancel_appointment1on1($appointment1on1['ID']);
            $cancelled = self::$birchschedule->model->booking->if_appointment_cancelled($appointment_id);
            if($cancelled) {
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
            }
        }
        self::$birchschedule->view->render_ajax_success_message($success);
    }

}

Birchschedule_View_Appointments_Edit_Clientlist_Cancel_Imp::init_vars();

?>