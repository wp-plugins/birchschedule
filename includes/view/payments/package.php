<?php

if(!class_exists('Birchschedule_View_Payments')) {

    class Birchschedule_View_Payments extends Birchpress_Lang_Package {
        private static $instance;

        private function __construct() {
        }

        public function define_interface() {

            $this->define_function('init');

            $this->define_function('wp_admin_init');

            $this->define_function('get_payments_details_html');

            $this->define_function('get_payment_types');

            $this->define_function('save_payments');

            $this->define_function('ajax_add_new_payment');

            $this->define_function('get_payments_by_appointment');
            
        }

        static function get_instance() {
            if(!self::$instance) {
                self::$instance = new Birchschedule_View_Payments();
            }
            return self::$instance;
        }
    }

    $GLOBALS['birchschedule']->view->payments = Birchschedule_View_Payments::get_instance();

    require_once 'imp.php';    
}
