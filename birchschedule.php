<?php

/*
  Plugin Name: BirchPress Scheduler
  Plugin URI: http://www.birchpress.com
  Description: An appointment booking and online scheduling plugin that allows service businesses to take online bookings.
  Version: 1.6.2
  Author: BirchPress
  Author URI: http://www.birchpress.com
  License: GPLv2
 */

if (!defined('ABSPATH'))
    exit;

@ini_set('memory_limit', -1);
if (!class_exists('Birchschedule')) :

    class Birchschedule {

        private $plugin_url;
        public $settings_view;
        public $locations_view;
        public $services_view;
        public $staff_view;
        public $clients_view;
        public $calendar_view;
        public $payment_view;
        public $help_view;
        public $shortcode;
        public $addons;
        public $merge_fields;
        public $upgrader;
        
        public $product_name;
        public $product_code;
        public $product_version;
        public $admin_capability;

        public $temp_data;

        public function __construct() {
            $this->product_version = '1.6.2';
            $this->product_name = 'BirchPress Scheduler';
            $this->product_code = 'birchschedule';
            $this->admin_capability = 'publish_pages';
            
            $this->includes();

            $this->util = BIRS_Util::get_instance();
            BIRS_Calendar_Logic::get_instance();
            $this->merge_fields = BIRS_Merge_Fields::get_instance();
            $this->upgrader = new BIRS_Upgrader();
            
            $this->settings_view = new BIRS_Settings_View();
            $this->help_view = new BIRS_Help_View();
            $this->locations_view = new BIRS_Locations_View();
            $this->services_view = new BIRS_Services_View();
            $this->staff_view = new BIRS_Staff_View();
            $this->clients_view = new BIRS_Clients_View();
            $this->calendar_view = new BIRS_Calendar_View();
            $this->payment_view = new BIRS_Payment_View();
            $this->shortcode = new BIRS_Shortcode();
            add_action('admin_menu', array(&$this, 'create_admin_menu'));
            add_action('init', array($this, 'init'));
            add_action('plugins_loaded', array($this, 'load_i18n'));
            $this->addons = array();
            $this->temp_data = array(
                'shortcodes' => array()
            );
        }

        public function init() {
            $this->register_common_scripts();
            $this->register_common_styles();
        }

        function load_i18n() {
            load_plugin_textdomain('birchschedule', false, 'birchschedule/languages');
        }

        function register_common_scripts() {
            $version = $this->product_version;
            wp_register_script('underscore', 
                $this->plugin_url() . '/assets/js/underscore/underscore-min.js', 
                array(), '1.4.2');
            wp_register_script('moment', 
                $this->plugin_url() . '/assets/js/moment/moment.min.js', 
                array(), '1.7.0');
            wp_register_script('select2', 
                $this->plugin_url() . '/assets/js/select2/select2.min.js', 
                array('jquery'), '3.3.2');
            wp_register_script('jgrowl', 
                $this->plugin_url() . '/assets/js/jgrowl/jquery.jgrowl.js', 
                array('jquery'), '1.2.5');
            wp_register_script('jscolor', 
                $this->plugin_url() . '/assets/js/jscolor/jscolor.js', 
                array(), '1.4.0');
            wp_register_script('birs_lib_fullcalendar', 
                $this->plugin_url() . '/assets/js/fullcalendar/fullcalendar.js', 
                array('jquery-ui-draggable', 'jquery-ui-resizable',
                'jquery-ui-dialog', 'jquery-ui-datepicker',
                'jquery-ui-tabs', 'jquery-ui-autocomplete'), '1.5.4');
            wp_register_script('birs_filedownload', 
                $this->plugin_url() . '/assets/js/filedownload/jquery.fileDownload.js',
                array('jquery'), '1.4.0');

            wp_register_script('birs_common', 
                $this->plugin_url() . '/assets/js/common.js', 
                array('jquery', 'underscore'), "$version");
            wp_register_script('birs_admin_common', 
                $this->plugin_url() . '/assets/js/admin/common.js', 
                array('jquery', 'underscore', 'jgrowl', 'birs_common'), "$version");
            wp_register_script('birs_admin_client', 
                $this->plugin_url() . '/assets/js/admin/client.js', 
                array('birs_admin_common'), "$version");
            wp_register_script('birs_admin_location', 
                $this->plugin_url() . '/assets/js/admin/location.js', 
                array('birs_admin_common'), "$version");
            wp_register_script('birs_admin_service', 
                $this->plugin_url() . '/assets/js/admin/service.js', 
                array('birs_admin_common'), "$version");
            wp_register_script('birs_admin_staff', 
                $this->plugin_url() . '/assets/js/admin/staff.js', 
                array('jquery', 'jscolor'), "$version");
            wp_register_script('birs_admin_calendar', 
                $this->plugin_url() . '/assets/js/admin/calendar.js', 
                array('birs_lib_fullcalendar', 'moment', 'birs_admin_common', 'select2'), "$version");
            wp_register_script('birs_admin_appointment_edit', 
                $this->plugin_url() . '/assets/js/admin/appointment-edit.js', 
                array('birs_admin_calendar', 'select2'), "$version");
            wp_register_script('birchschedule', 
                $this->plugin_url() . '/assets/js/birchschedule.js', 
                array('jquery-ui-datepicker', 'underscore', 'birs_common', 'select2'), "$version");
        }

        function register_common_styles() {
            $version = $this->product_version;
            wp_register_style('birs_lib_fullcalendar', 
                $this->plugin_url() . '/assets/js/fullcalendar/fullcalendar.css', 
                array(), '1.5.4');
            wp_register_style('jquery-ui-bootstrap', 
                $this->plugin_url() . '/assets/css/jquery-ui-bootstrap/jquery-ui-1.9.2.custom.css', 
                array(), '0.22');
            wp_register_style('jquery-ui-no-theme', 
                $this->plugin_url() . '/assets/css/jquery-ui-no-theme/jquery-ui-1.9.2.custom.css', 
                array(), '1.9.2');
            wp_register_style('jquery-ui-smoothness', 
                $this->plugin_url() . '/assets/css/jquery-ui-smoothness/jquery-ui-1.9.2.custom.css', 
                array(), '1.9.2');
            wp_register_style('jquery-wijmo-open', 
                $this->plugin_url() . '/assets/css/jquery-ui-bootstrap/jquery.wijmo-open.1.5.0.css', 
                array(), '1.5.0');
            wp_register_style('select2', 
                $this->plugin_url() . '/assets/js/select2/select2.css', 
                array(), '3.3.2');
            wp_register_style('jgrowl', 
                $this->plugin_url() . '/assets/js/jgrowl/jquery.jgrowl.css', 
                array(), '1.2.5');
            wp_register_style('birchschedule_admin_styles', 
                $this->plugin_url() . '/assets/css/admin.css', 
                array('jgrowl', 'select2'), "$version");
            wp_register_style('birchschedule_admin_calendar', 
                $this->plugin_url() . '/assets/css/admin/calendar.css', 
                array('jgrowl', 'select2'), "$version");
            wp_register_style('birchschedule_admin_services', 
                $this->plugin_url() . '/assets/css/admin/services.css', 
                array('jgrowl', 'select2'), "$version");
            wp_register_style('birchschedule_admin_staff', 
                $this->plugin_url() . '/assets/css/admin/staff.css', 
                array('jgrowl', 'select2'), "$version");
            wp_register_style('birchschedule_styles', 
                $this->plugin_url() . '/assets/css/birchschedule.css',
                array('select2', 'jquery-ui-no-theme'), "$version");
        }

        private function includes() {
            require_once 'classes/birs-util.php';
            require_once 'classes/model/birs-model.php';
            require_once 'classes/model/birs-model-registry.php';
            require_once 'classes/model/birs-model-factory.php';
            require_once 'classes/model/birs-model-query.php';
            require_once 'classes/model/birs-location.php';
            require_once 'classes/model/birs-staff.php';
            require_once 'classes/model/birs-service.php';
            require_once 'classes/model/birs-client.php';
            require_once 'classes/model/birs-appointment.php';
            require_once 'classes/model/birs-payment.php';
            require_once 'classes/logic/birs-calendar-logic.php';
            require_once 'classes/logic/birs-merge-fields.php';
            require_once 'classes/view/birs-admin-view.php';
            require_once 'classes/view/birs-content-view.php';
            require_once 'classes/view/birs-settings-view.php';
            require_once 'classes/view/birs-settings-tab-view.php';
            require_once 'classes/view/birs-help-view.php';
            require_once 'classes/view/birs-locations-view.php';
            require_once 'classes/view/birs-services-view.php';
            require_once 'classes/view/birs-staff-view.php';
            require_once 'classes/view/birs-clients-view.php';
            require_once 'classes/view/birs-calendar-view.php';
            require_once 'classes/view/birs-payment-view.php';
            require_once 'classes/view/birs-shortcode.php';
            require_once 'classes/birs-addon.php';
            require_once 'classes/birs-upgrader.php';
            $this->register_model_classes();
        }

        public function load_addons() {
            $addons_dir = plugin_dir_path(__FILE__) . 'addons';
            if (is_dir($addons_dir)) {
                $addons = scandir($addons_dir);
                if ($addons) {
                    foreach ($addons as $addon) {
                        if ($addon != '.' && $addon != '..' && $addon != 'lib') {
                            $addon_main_file = $addons_dir . '/' . $addon . '/index.php';

                            if (is_file($addon_main_file)) {
                                include_once $addon_main_file;
                            }
                        }
                    }
                }
            }
        }

        public function register_model_classes() {
            $registry = BIRS_Model_Registry::get_instance();
            $registry->register_model('birs_appointment', 'BIRS_Appointment');
            $registry->register_model('birs_client', 'BIRS_Client');
            $registry->register_model('birs_location', 'BIRS_Location');
            $registry->register_model('birs_service', 'BIRS_Service');
            $registry->register_model('birs_staff', 'BIRS_Staff');
            $registry->register_model('birs_payment', 'BIRS_Payment');
        }

        public function create_admin_menu() {
            if(!current_user_can("edit_posts")) {
                return;
            }
            global $menu;

            $this->create_menu_birchschedule('30.26929');
            $this->reorder_submenus();
        }

        private function create_menu_birchschedule($position) {
            $icon_url = $this->plugin_url . '/assets/images/birchschedule_16.png';
            add_menu_page(__('Scheduler', 'birchschedule'), 
                __('Scheduler', 'birchschedule'), 'edit_posts', 
                'birchschedule_schedule', '', $icon_url, $position);
            $this->calendar_view->page_hook = 
                add_submenu_page('birchschedule_schedule', __('Calendar', 'birchschedule'), 
                    __('Calendar', 'birchschedule'), 'edit_posts', 'birchschedule_calendar', 
                    array(&$this->calendar_view, 'render_admin_page'));
            $this->settings_view->page_hook = 
                add_submenu_page('birchschedule_schedule', 
                    __('BirchPress Scheduler Settings', 'birchschedule'), 
                    __('Settings', 'birchschedule'), $this->admin_capability, 
                    'birchschedule_settings', array(&$this->settings_view, 'render_admin_page'));
            add_submenu_page('birchschedule_schedule', 
                __('Help', 'birchschedule'), __('Help', 'birchschedule'), 
                'edit_posts', 'birchschedule_help', array(&$this->help_view, 'render_admin_page'));
            remove_submenu_page('birchschedule_schedule', 'birchschedule_schedule');
        }

        private function reorder_submenus() {
            global $submenu;
            $sub_items = &$submenu['birchschedule_schedule'];
            $location = $this->get_submenu($sub_items, 'location');
            $staff = $this->get_submenu($sub_items, 'staff');
            $service = $this->get_submenu($sub_items, 'service');
            $client = $this->get_submenu($sub_items, 'client');
            $calendar = $this->get_submenu($sub_items, 'calendar');
            $settings = $this->get_submenu($sub_items, 'settings');
            $help = $this->get_submenu($sub_items, 'help');
            if(current_user_can($this->admin_capability)) {
                $sub_items = array(
                    $calendar,
                    $location,
                    $staff,
                    $service,
                    $client,
                    $settings,
                    $help
                );
            } else {
                $sub_items = array(
                    $calendar,
                    $help
                );
            }
        }

        private function get_submenu($submenus, $name) {
            foreach ($submenus as $submenu) {
                if (strpos($submenu[2], $name)) {
                    return $submenu;
                }
            }
            return false;
        }

        public function plugin_url() {
            if ($this->plugin_url)
                return $this->plugin_url;
            return $this->plugin_url = plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
        }

        public function plugin_file_path() {
            return __FILE__;
        }

    }

    $GLOBALS['birchschedule'] = new Birchschedule();
    $GLOBALS['birchschedule']->load_addons();
    $GLOBALS['birchschedule']->upgrader->upgrade();

endif;
