<?php
/*
  Plugin Name: BirchSchedule
  Plugin URI: http://www.birchpress.com
  Description: An appointment booking toolkit that allows service businesses to take reservations online.
  Version: 1.0.2
  Author: BirchPress
  Author URI: http://www.birchpress.com
  License: GPLv2
 */

if (!defined('ABSPATH'))
    exit;

if (!class_exists('Birchschedule')) :

    class Birchschedule {

        private $plugin_url;
        private $settings_view;
        private $locations_view;
        private $services_view;
        private $staff_view;
        private $clients_view;
        private $calendar_view;
        private $shortcode;
        public $calendar_hook_suffix;

        public function __construct() {
            $this->includes();

            $this->settings_view = new BIRS_Settings_View();
            $this->locations_view = new BIRS_Locations_View();
            $this->services_view = new BIRS_Services_View();
            $this->staff_view = new BIRS_Staff_View();
            $this->clients_view = new BIRS_Clients_View();
            $this->calendar_view = new BIRS_Calendar_View();
            $this->shortcode = new BIRS_Shortcode();
            add_action('admin_menu', array(&$this, 'create_admin_menu'));
        }

        public function get_calendar_view() {
            return $this->calendar_view;
        }

        public function get_services_view() {
            return $this->services_view;
        }

        private function includes() {
            require_once 'classes/birs-util.php';
            require_once 'classes/model/birs-model.php';
            require_once 'classes/model/birs-model-query.php';
            require_once 'classes/model/birs-location.php';
            require_once 'classes/model/birs-staff.php';
            require_once 'classes/model/birs-service.php';
            require_once 'classes/model/birs-client.php';
            require_once 'classes/model/birs-appointment.php';
            require_once 'classes/birs-admin-view.php';
            require_once 'classes/birs-content-view.php';
            require_once 'classes/birs-settings-view.php';
            require_once 'classes/birs-locations-view.php';
            require_once 'classes/birs-services-view.php';
            require_once 'classes/birs-staff-view.php';
            require_once 'classes/birs-clients-view.php';
            require_once 'classes/birs-calendar-view.php';
            require_once 'classes/birs-shortcode.php';
        }

        public function create_admin_menu() {
            global $menu;

            $position = 29;
            $menu[$position++] = array('', 'read', 'separator-birchschedule', '', 'wp-menu-separator birchschedule');
            $icon_url = $this->plugin_url . '/assets/images/birchschedule_16.png';
            add_menu_page(__('BirchSchedule', 'birchschedule'), __('BirchSchedule', 'birchschedule'), 'edit_posts', 'birchschedule_schedule', '', $icon_url, $position++);
            $this->calendar_hook_suffix = add_submenu_page('birchschedule_schedule', __('Calendar', 'birchschedule'), __('Calendar', 'birchschedule'), 'edit_posts', 'birchschedule_calendar', array(&$this->calendar_view, 'render_admin_page'));
            add_submenu_page('birchschedule_schedule', __('Settings', 'birchschedule'), __('Settings', 'birchschedule'), 'manage_options', 'birchschedule_settings', array(&$this->settings_view, 'render_admin_page'));
            remove_submenu_page('birchschedule_schedule', 'birchschedule_schedule');
            $this->reorder_submenus();
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
            $sub_items = array(
                $calendar,
                $location,
                $staff,
                $service,
                $client
            );
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

    }

    $birchschedule = new Birchschedule();

endif;