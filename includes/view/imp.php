<?php

class Birchschedule_View_Imp {

    private static $page_hooks;

    private function __construct() {

    }

    static function init() {
        global $birchschedule;
        $package = $birchschedule->view;
        $package->add_action('init', 'wp_init');
        $package->add_action('admin_init', 'wp_admin_init');
        $package->add_action('admin_menu', 'create_admin_menus');
        $package->add_action('plugins_loaded', 'load_i18n');
        self::init_page_hooks();
    }

    static function wp_init() {
        self::register_common_scripts();
        self::register_common_styles();
    }

    static function wp_admin_init() {
        add_action('load-post.php', array(__CLASS__, 'on_load_post'));
        add_action('load-post-new.php', array(__CLASS__, 'on_load_post_new'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'on_admin_enqueue_scripts'));
        add_action('save_post', array(__CLASS__, 'on_save_post'), 10, 2);
        add_filter('wp_insert_post_data', array(__CLASS__, 'on_wp_insert_post_data'), 10, 2);
    }

    static function init_page_hooks() {
        self::$page_hooks = array();
    }

    function get_current_post_type() {
        global $post, $typenow, $current_screen;

        if ( $post && $post->post_type ) {
            return $post->post_type;
        }
        elseif( $typenow ) {
            return $typenow;
        } 
        elseif( $current_screen && $current_screen->post_type ) {
            return $current_screen->post_type;
        }

        return '';
    }

    static function on_load_post() {
        global $birchschedule;

        $post_type = $birchschedule->view->get_current_post_type();
        $birchschedule->view->load_page_edit(array(
            'post_type' => $post_type
        ));
    }

    static function on_load_post_new() {
        global $birchschedule;

        $post_type = $birchschedule->view->get_current_post_type();
        $birchschedule->view->load_page_edit(array(
            'post_type' => $post_type
        ));
    }

    static function load_page_edit($arg) {}

    static function on_admin_enqueue_scripts($hook) {
        global $birchschedule;

        if($hook == 'post.php' || $hook == 'post-new.php') {
            $post_type = $birchschedule->view->get_current_post_type();
            $birchschedule->view->enqueue_scripts_edit(array(
                'post_type' => $post_type
            ));
        }
    }

    static function save_post($post) {}

    static function on_save_post($post_id, $post) {
        if (empty($post_id) || empty($post) || empty($_POST))
            return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (is_int(wp_is_post_revision($post)))
            return;
        if (is_int(wp_is_post_autosave($post)))
            return;
        if (!current_user_can('edit_post', $post_id))
            return;

        global $birchschedule;

        $post_a = (array)$post;
        $birchschedule->view->save_post($post_a);
    }

    static function pre_save_post($post_data, $post_attr) { return $post_data; }

    static function on_wp_insert_post_data($post_data, $post_attr) {
        global $birchschedule;

        if($post_data['post_status'] == 'auto-draft') {
            return $post_data;
        }
        return $birchschedule->view->pre_save_post($post_data, $post_attr);
    }

    static function enqueue_scripts_edit($arg) {}

    static function enqueue_scripts($scripts) {
        foreach ($scripts as $script) {
            if(is_array($script)) {
                wp_enqueue_script($script[0]);
                wp_localize_script($script[0], $script[1], $script[2]);
            } else {
                wp_enqueue_script($script);
            }
        }
    }

    static function enqueue_styles($styles) {
        foreach ($styles as $style) {
            wp_enqueue_style($style);
        }
    }

    static function merge_request($model, $config) {
        $request = $_REQUEST;
        birch_assert(is_array($config) && isset($config['base_keys']) && 
            isset($config['meta_keys']));
        foreach ($config['base_keys'] as $key) {
            if (isset($request[$key])) {
                $model[$key] = $request[$key];
            } else {
                $model[$key] = null;
            }
        }
        foreach ($config['meta_keys'] as $key) {
            $req_key = substr($key, 1);
            if (isset($request[$req_key])) {
                $model[$key] = $request[$req_key];
            } else {
                $model[$key] = null;
            }
        }
        return $model;
    }

    static function apply_currency_to_label($label, $currency_code) {
        global $birchpress, $birchschedule;

        $currencies = $birchpress->util->get_currencies();
        $currency = $currencies[$currency_code];
        $symbol = $currency['symbol_right'];
        if ($symbol == '') {
            $symbol = $currency['symbol_left'];
        }
        return $label = $label . ' (' . $symbol . ')';
    }

    static function render_errors() {
        global $birchschedule;

        $errors = $birchschedule->view->get_errors();
        if ($errors && sizeof($errors) > 0) {
            echo '<div id="birchschedule_errors" class="error fade">';
            foreach ($errors as $error) {
                echo '<p>' . $error . '</p>';
            }
            echo '</div>';
            update_option('birchschedule_errors', '');
        }
    }

    static function get_errors() {
        return get_option('birchschedule_errors');
    }

    static function has_errors() {
        global $birchschedule;

        $errors = $birchschedule->view->get_errors();
        if ($errors && sizeof($errors) > 0) {
            return true;
        } else {
            return false;
        }
    }

    static function save_errors($errors) {
        update_option('birchschedule_errors', $errors);
    }

    static function get_screen($hook_name) {
        if(substr($hook_name, -4) !== '.php') {
            $hook_name = $hook_name . '.php';
        }
        $screen = WP_Screen::get($hook_name);
        return $screen;
    }

    static function show_notice() {}

    static function add_page_hook($key, $hook) {
        self::$page_hooks[$key] = $hook;
    }

    static function get_page_hook($key) {
        return self::$page_hooks[$key];
    }

    static function get_custom_code_css($shortcode) {
        return '';
    }

    static function load_i18n() {
        load_plugin_textdomain('birchschedule', false, 'birchschedule/languages');
    }

    static function create_admin_menus() {
        if(!current_user_can("edit_posts")) {
            return;
        }
        global $menu;

        self::create_menu_scheduler('30.26929');
        self::reorder_submenus();
    }

    static function create_menu_scheduler($position) {
        global $birchschedule;
        $package = $birchschedule->view;

        $icon_url = $birchschedule->plugin_url() . '/assets/images/birchschedule_16.png';

        add_menu_page(__('Scheduler', 'birchschedule'), 
            __('Scheduler', 'birchschedule'), 'edit_posts', 
            'birchschedule_schedule', '', $icon_url, $position);

        $page_hook_calendar = 
            add_submenu_page('birchschedule_schedule', __('Calendar', 'birchschedule'), 
                __('Calendar', 'birchschedule'), 'edit_posts', 'birchschedule_calendar', 
                array($package, 'render_calendar_page'));
        $package->add_page_hook('calendar', $page_hook_calendar);

        $page_hook_settings = 
            add_submenu_page('birchschedule_schedule', 
                __('BirchPress Scheduler Settings', 'birchschedule'), 
                __('Settings', 'birchschedule'), 'publish_pages', 
                'birchschedule_settings', array($package, 'render_settings_page'));
        $package->add_page_hook('settings', $page_hook_settings);

        $page_hook_help = add_submenu_page('birchschedule_schedule', 
            __('Help', 'birchschedule'), __('Help', 'birchschedule'), 
            'edit_posts', 'birchschedule_help', array($package, 'render_help_page'));
        $package->add_page_hook('help', $page_hook_help);

        remove_submenu_page('birchschedule_schedule', 'birchschedule_schedule');
    }

    static function reorder_submenus() {
        global $submenu;

        $sub_items = &$submenu['birchschedule_schedule'];
        $location = self::get_submenu($sub_items, 'location');
        $staff = self::get_submenu($sub_items, 'staff');
        $service = self::get_submenu($sub_items, 'service');
        $client = self::get_submenu($sub_items, 'client');
        $calendar = self::get_submenu($sub_items, 'calendar');
        $settings = self::get_submenu($sub_items, 'settings');
        $help = self::get_submenu($sub_items, 'help');
        if(current_user_can('publish_pages')) {
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

    static function get_submenu($submenus, $name) {
        foreach ($submenus as $submenu) {
            if (strpos($submenu[2], $name)) {
                return $submenu;
            }
        }
        return false;
    }

    static function register_common_scripts() {
        global $birchschedule;
        $version = $birchschedule->product_version;
        wp_register_script('underscore', 
            $birchschedule->plugin_url() . '/assets/js/underscore/underscore-min.js', 
            array(), '1.4.2');
        wp_register_script('moment', 
            $birchschedule->plugin_url() . '/assets/js/moment/moment.min.js', 
            array(), '1.7.0');
        wp_register_script('select2', 
            $birchschedule->plugin_url() . '/assets/js/select2/select2.min.js', 
            array('jquery'), '3.3.2');
        wp_register_script('jgrowl', 
            $birchschedule->plugin_url() . '/assets/js/jgrowl/jquery.jgrowl.js', 
            array('jquery'), '1.2.5');
        wp_register_script('jscolor', 
            $birchschedule->plugin_url() . '/assets/js/jscolor/jscolor.js', 
            array(), '1.4.0');
        wp_register_script('birs_lib_fullcalendar', 
            $birchschedule->plugin_url() . '/assets/js/fullcalendar/fullcalendar.js', 
            array('jquery-ui-draggable', 'jquery-ui-resizable',
            'jquery-ui-dialog', 'jquery-ui-datepicker',
            'jquery-ui-tabs', 'jquery-ui-autocomplete'), '1.5.4');
        wp_register_script('birs_filedownload', 
            $birchschedule->plugin_url() . '/assets/js/filedownload/jquery.fileDownload.js',
            array('jquery'), '1.4.0');

        wp_register_script('birs_common', 
            $birchschedule->plugin_url() . '/assets/js/common.js', 
            array('jquery', 'underscore'), "$version");
        wp_register_script('birs_admin_common', 
            $birchschedule->plugin_url() . '/assets/js/admin/common.js', 
            array('jquery', 'underscore', 'jgrowl', 'birs_common'), "$version");
        wp_register_script('birs_admin_client', 
            $birchschedule->plugin_url() . '/assets/js/admin/client.js', 
            array('birs_admin_common'), "$version");
        wp_register_script('birs_admin_location', 
            $birchschedule->plugin_url() . '/assets/js/admin/location.js', 
            array('birs_admin_common'), "$version");
        wp_register_script('birs_admin_service', 
            $birchschedule->plugin_url() . '/assets/js/admin/service.js', 
            array('birs_admin_common'), "$version");
        wp_register_script('birs_admin_staff', 
            $birchschedule->plugin_url() . '/assets/js/admin/staff.js', 
            array('jquery', 'jscolor'), "$version");
        wp_register_script('birs_admin_calendar', 
            $birchschedule->plugin_url() . '/assets/js/admin/calendar.js', 
            array('birs_lib_fullcalendar', 'moment', 'birs_admin_common', 'select2'), "$version");
        wp_register_script('birs_admin_appointment_edit', 
            $birchschedule->plugin_url() . '/assets/js/admin/appointment-edit.js', 
            array('birs_admin_calendar', 'select2'), "$version");
        wp_register_script('birchschedule', 
            $birchschedule->plugin_url() . '/assets/js/birchschedule.js', 
            array('jquery-ui-datepicker', 'underscore', 'birs_common', 'select2'), "$version");
    }

    static function register_common_styles() {
        global $birchschedule;
        $version = $birchschedule->product_version;
        wp_register_style('birs_lib_fullcalendar', 
            $birchschedule->plugin_url() . '/assets/js/fullcalendar/fullcalendar.css', 
            array(), '1.5.4');
        wp_register_style('jquery-ui-bootstrap', 
            $birchschedule->plugin_url() . '/assets/css/jquery-ui-bootstrap/jquery-ui-1.9.2.custom.css', 
            array(), '0.22');
        wp_register_style('jquery-ui-no-theme', 
            $birchschedule->plugin_url() . '/assets/css/jquery-ui-no-theme/jquery-ui-1.9.2.custom.css', 
            array(), '1.9.2');
        wp_register_style('jquery-ui-smoothness', 
            $birchschedule->plugin_url() . '/assets/css/jquery-ui-smoothness/jquery-ui-1.9.2.custom.css', 
            array(), '1.9.2');
        wp_register_style('jquery-wijmo-open', 
            $birchschedule->plugin_url() . '/assets/css/jquery-ui-bootstrap/jquery.wijmo-open.1.5.0.css', 
            array(), '1.5.0');
        wp_register_style('select2', 
            $birchschedule->plugin_url() . '/assets/js/select2/select2.css', 
            array(), '3.3.2');
        wp_register_style('jgrowl', 
            $birchschedule->plugin_url() . '/assets/js/jgrowl/jquery.jgrowl.css', 
            array(), '1.2.5');
        wp_register_style('birchschedule_admin_styles', 
            $birchschedule->plugin_url() . '/assets/css/admin.css', 
            array('jgrowl', 'select2'), "$version");
        wp_register_style('birchschedule_admin_calendar', 
            $birchschedule->plugin_url() . '/assets/css/admin/calendar.css', 
            array('jgrowl', 'select2'), "$version");
        wp_register_style('birchschedule_admin_services', 
            $birchschedule->plugin_url() . '/assets/css/admin/services.css', 
            array('jgrowl', 'select2'), "$version");
        wp_register_style('birchschedule_admin_staff', 
            $birchschedule->plugin_url() . '/assets/css/admin/staff.css', 
            array('jgrowl', 'select2'), "$version");
        wp_register_style('birchschedule_styles', 
            $birchschedule->plugin_url() . '/assets/css/birchschedule.css',
            array('select2', 'jquery-ui-no-theme'), "$version");
    }

}
