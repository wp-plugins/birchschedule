<?php

final class Birchschedule_View_Imp {

    private static $page_hooks;

    private static $scripts_data;

    private static $enqueued_scripts;

    private static $localized_scripts;

    static function init() {
        global $birchschedule;
        $package = $birchschedule->view;

        $birchschedule->add_less_dir($birchschedule->plugin_dir_path() . 'assets/css/birchschedule');

        self::$scripts_data = array();

        self::$enqueued_scripts = array();

        self::$localized_scripts = array();

        add_action('init', array($package, 'wp_init'));

        add_action('admin_init', array($package, 'wp_admin_init'));

        add_action('admin_menu', array($package, 'create_admin_menus'));

        add_action('plugins_loaded', array($package, 'load_i18n'));

        self::init_page_hooks();
    }

    static function init_doing_ajax() {

    }

    static function wp_init() {        
        global $birchschedule;
        $package = $birchschedule->view;

        if(!defined('DOING_AJAX')) {
            $package->register_common_scripts();
            $package->register_common_styles();
            $package->register_common_scripts_data_fns();

            add_action('wp_print_scripts', 
                array($package, 'localize_scripts'));

            add_action('wp_print_footer_scripts',
                array($package, 'localize_scripts'), 9);
        }

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

    static function get_current_post_type() {
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
        $birchschedule->view->load_post_edit(array(
            'post_type' => $post_type
        ));
    }

    static function on_load_post_new() {
        global $birchschedule;

        $post_type = $birchschedule->view->get_current_post_type();
        $birchschedule->view->load_page_edit(array(
            'post_type' => $post_type
        ));
        $birchschedule->view->load_post_new(array(
            'post_type' => $post_type
        ));
    }

    static function load_page_edit($arg) {}

    static function load_post_new($arg) {}

    static function load_post_edit($arg) {}

    static function on_admin_enqueue_scripts($hook) {
        global $birchschedule;

        if($hook == 'post-new.php') {
            $post_type = $birchschedule->view->get_current_post_type();
            $birchschedule->view->enqueue_scripts_post_new(array(
                'post_type' => $post_type
            ));
            $birchschedule->view->enqueue_scripts_edit(array(
                'post_type' => $post_type
            ));
        }
        if($hook == 'post.php') {
            $post_type = $birchschedule->view->get_current_post_type();
            $birchschedule->view->enqueue_scripts_post_edit(array(
                'post_type' => $post_type
            ));
            $birchschedule->view->enqueue_scripts_edit(array(
                'post_type' => $post_type
            ));
        }
        if($hook == 'edit.php' && isset($_GET['post_type'])) {
            $post_type = $_GET['post_type'];
            $birchschedule->view->enqueue_scripts_list(array(
                'post_type' => $post_type
            ));
        }
    }

    static function save_post($post) {}

    static function on_save_post($post_id, $post) {
        if(!isset($_POST['action']) || $_POST['action'] !== 'editpost') {
            return;
        }
        if (empty($post_id) || empty($post) || empty($_POST))
            return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (is_int(wp_is_post_revision($post)))
            return;
        if (is_int(wp_is_post_autosave($post)))
            return;

        global $birchschedule;

        $post_a = (array)$post;
        $birchschedule->view->save_post($post_a);
    }

    static function pre_save_post($post_data, $post_attr) { return $post_data; }

    static function on_wp_insert_post_data($post_data, $post_attr) {
        if(!isset($_POST['action']) || $_POST['action'] !== 'editpost') {
            return $post_data;
        }
        global $birchschedule;

        if($post_data['post_status'] == 'auto-draft') {
            return $post_data;
        }
        return $birchschedule->view->pre_save_post($post_data, $post_attr);
    }

    static function enqueue_scripts_post_new($arg) {}

    static function enqueue_scripts_post_edit($arg) {}

    static function enqueue_scripts_edit($arg) {}

    static function enqueue_scripts_list($arg) {}

    static function enqueue_scripts($scripts) {
        if(is_string($scripts)) {
            $scripts = array($scripts);
        }
        foreach ($scripts as $script) {
            wp_enqueue_script($script);
        }
        self::$enqueued_scripts = array_merge(self::$enqueued_scripts, $scripts);
        self::$enqueued_scripts = array_unique(self::$enqueued_scripts);
    }

    static function enqueue_styles($styles) {
        if(is_string($styles)) {
            wp_enqueue_style($styles);
            return;
        }
        if(is_array($styles)) {
            foreach ($styles as $style) {
                if(is_string($style)) {
                    wp_enqueue_style($style);
                }
            }
        }
    }

    static function merge_request($model, $config, $request) {
        global $birchschedule;

        return $birchschedule->model->merge_data($model, $config, $request);
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
        if(isset(self::$page_hooks[$key])) {
            return self::$page_hooks[$key];
        } else {
            return '';
        }
    }

    static function get_custom_code_css($shortcode) {
        return '';
    }

    static function get_shortcodes() {
        return array();
    }

    static function load_i18n() {
        load_plugin_textdomain('birchschedule', false, 'birchschedule/languages');
    }

    static function create_admin_menus() {
        global $menu, $birchschedule;

        $package = $birchschedule->view;
        $package->create_menu_scheduler('30.26929');
        $package->reorder_submenus();
    }

    static function create_menu_scheduler($position) {
        global $birchschedule;
        $package = $birchschedule->view;

        $icon_url = $birchschedule->plugin_url() . '/assets/images/birchschedule_16.png';

        add_menu_page(__('Scheduler', 'birchschedule'), 
            __('Scheduler', 'birchschedule'), 'edit_birs_appointments', 
            'birchschedule_schedule', '', $icon_url, $position);

        $page_hook_calendar = 
            add_submenu_page('birchschedule_schedule', __('Calendar', 'birchschedule'), 
                __('Calendar', 'birchschedule'), 'edit_birs_appointments', 'birchschedule_calendar', 
                array($package, 'render_calendar_page'));
        $package->add_page_hook('calendar', $page_hook_calendar);

        $page_hook_settings = 
            add_submenu_page('birchschedule_schedule', 
                __('BirchPress Scheduler Settings', 'birchschedule'), 
                __('Settings', 'birchschedule'), 'manage_birs_settings', 
                'birchschedule_settings', array($package, 'render_settings_page'));
        $package->add_page_hook('settings', $page_hook_settings);

        $page_hook_help = add_submenu_page('birchschedule_schedule', 
            __('Help', 'birchschedule'), __('Help', 'birchschedule'), 
            'read', 'birchschedule_help', array($package, 'render_help_page'));
        $package->add_page_hook('help', $page_hook_help);
    }

    static function reorder_submenus() {
        global $submenu, $birchschedule;

        $package = $birchschedule->view;
        $sub_items = &$submenu['birchschedule_schedule'];
        $location = $package->get_submenu($sub_items, 'location');
        $staff = $package->get_submenu($sub_items, 'staff');
        $service = $package->get_submenu($sub_items, 'service');
        $client = $package->get_submenu($sub_items, 'client');
        $calendar = $package->get_submenu($sub_items, 'calendar');
        $settings = $package->get_submenu($sub_items, 'settings');
        $help = $package->get_submenu($sub_items, 'help');
        $sub_items = array(
            $calendar,
            $location,
            $staff,
            $service,
            $client,
            $settings,
            $help
        );
    }

    static function get_submenu($submenus, $name) {
        foreach ($submenus as $submenu) {
            if (strpos($submenu[2], $name)) {
                return $submenu;
            }
        }
        return false;
    }

    static function register_script_data_fn($handle, $data_name, $fn) {
        if(isset(self::$scripts_data[$handle])) {
            self::$scripts_data[$handle][$data_name] = $fn;
        } else {
            self::$scripts_data[$handle] = array(
                $data_name => $fn
            );
        }
    }

    static function localize_scripts() {
        global $wp_scripts;

        $wp_scripts->all_deps(self::$enqueued_scripts, true);
        $all_scripts = $wp_scripts->to_do;

        $scripts_data = self::$scripts_data;

        foreach($all_scripts as $script) {
            if(isset($scripts_data[$script]) && 
                !in_array($script, self::$localized_scripts)) {
                foreach($scripts_data[$script] as $data_name => $data_fn) {
                    $data = call_user_func($data_fn);
                    wp_localize_script($script, $data_name, $data);
                }
                self::$localized_scripts[] = $script;
                self::$localized_scripts = array_unique(self::$localized_scripts);
            }
        }
    }

    static function get_admin_i18n_messages() {
        global $birchschedule;
        return $birchschedule->view->get_frontend_i18n_messages();
    }

    static function get_frontend_i18n_messages() {
        return array(
            'Loading...' => __('Loading...', 'birchschedule'),
            'Loading appointments...' => __('Loading appointments...', 'birchschedule'),
            'Saving...' => __('Saving...', 'birchschedule'),
            'Save' => __('Save', 'birchschedule'),
            'Please wait...' => __('Please wait...', 'birchschedule'),
            'Schedule' => __('Schedule', 'birchschedule'),
            'Are you sure you want to cancel this appointment?' => __('Are you sure you want to cancel this appointment?', 'birchschedule'),
            'Your appointment has been cancelled successfully.' => __('Your appointment has been cancelled successfully.', 'birchschedule'),
            "The appointment doesn't exist or has been cancelled." => __("The appointment doesn't exist or has been cancelled.", 'birchschedule'),
            'Your appointment has been rescheduled successfully.' => __('Your appointment has been rescheduled successfully.', 'birchschedule'),
            'Your appointment can not be cancelled now according to our booking policies.' => __('Your appointment can not be cancelled now according to our booking policies.', 'birchschedule'),
            'Your appointment can not be rescheduled now according to our booking policies.' => __('Your appointment can not be rescheduled now according to our booking policies.', 'birchschedule'),
            'There are no available times.' => __('There are no available times.', 'birchschedule'),
            '(Deposit)' => __('(Deposit)', 'birchschedule'),
            'Reschedule' => __('Reschedule', 'birchschedule'),
            'Change' => __('Change', 'birchschedule')
        );
    }

    static function render_ajax_success_message($success) {
        ?>
        <div id="birs_success" code="<?php echo $success['code']; ?>">
            <?php echo $success['message']; ?>
        </div>
        <?php
        exit;
    }

    static function render_ajax_error_messages($errors) {
        global $birchpress;

        if($birchpress->util->is_errors($errors)) {
            $error_arr = array();
            $codes = $birchpress->util->get_error_codes($errors);
            foreach($codes as $code) {
                $error_arr[$code] = $birchpress->util->get_error_message($errors, $code);
            }
        } else {
            $error_arr = $errors;
        }
        ?>
        <div id="birs_errors">
            <?php foreach ($error_arr as $error_id => $message): ?>
                <div id="<?php echo $error_id; ?>"><?php echo $message; ?></div>
            <?php endforeach; ?>
        </div>
        <?php
        exit;
    }

    static function get_query_array($query, $keys) {
        $source = array();
        $result = array();
        if(is_string($query)) {
            wp_parse_str($query, $source);
        }
        else if(is_array($query)) {
            $source = $query;
        }
        foreach($keys as $key) {
            if(isset($source[$key])) {
                $result[$key] = $source[$key];
            }
        }
        return $result;
    }

    static function get_query_string($query, $keys) {
        global $birchschedule;

        return http_build_query($birchschedule->view->get_query_array($query, $keys));
    }

    static function get_script_data_fn_model() {
        global $birchschedule, $birchpress;
        return array(
            'admin_url' => admin_url(),
            'ajax_url' => admin_url('admin-ajax.php'),
            'all_schedule' => $birchschedule->model->schedule->get_all_calculated_schedule(),
            'all_daysoff' => $birchschedule->model->get_all_daysoff(),
            'gmt_offset' => $birchpress->util->get_gmt_offset(),
            'future_time' => $birchschedule->model->get_future_time(),
            'cut_off_time' => $birchschedule->model->get_cut_off_time()
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
            'i18n_states' => $birchpress->util->get_states()
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

        $package->register_script_data_fn('birchschedule_model', 'birchschedule_model', 
            array($package, 'get_script_data_fn_model'));
        $package->register_script_data_fn('birchschedule_view', 'birchschedule_view', 
            array($package, 'get_script_data_fn_view'));
        $package->register_script_data_fn('birchschedule_view_admincommon', 'birchschedule_view_admincommon', 
            array($package, 'get_script_data_fn_admincommon'));
    }

    static function register_3rd_scripts() {
        global $birchschedule;
        $version = $birchschedule->product_version;

        wp_register_script('underscore', 
            $birchschedule->plugin_url() . '/assets/js/underscore/underscore-min.js', 
            array(), '1.6.0');

        wp_register_script('underscore_string', 
            $birchschedule->plugin_url() . '/assets/js/underscore/underscore.string.min.js', 
            array('underscore'), '2.3.0');

        wp_register_script('moment', 
            $birchschedule->plugin_url() . '/assets/js/moment/moment.min.js', 
            array(), '1.7.0');

        wp_register_script('jgrowl', 
            $birchschedule->plugin_url() . '/assets/js/jgrowl/jquery.jgrowl.js', 
            array('jquery'), '1.2.5');

        wp_register_script('jscolor', 
            $birchschedule->plugin_url() . '/assets/js/jscolor/jscolor.js', 
            array(), '1.4.0');

        wp_register_script('bootstrap', 
            $birchschedule->plugin_url() . '/assets/js/bootstrap/js/bootstrap.js',
            array('jquery'), '3.0.3');

        wp_deregister_script('select2');
        wp_register_script('select2', 
            $birchschedule->plugin_url() . '/assets/js/select2/select2.min.js', 
            array('jquery'), '3.4.2');

        wp_register_script('fullcalendar_birchpress', 
            $birchschedule->plugin_url() . '/assets/js/fullcalendar/fullcalendar_birchpress.js', 
            array('jquery-ui-draggable', 'jquery-ui-resizable',
            'jquery-ui-dialog', 'jquery-ui-datepicker',
            'jquery-ui-tabs', 'jquery-ui-autocomplete'), '1.6.4');
        
        wp_register_script('filedownload_birchpress', 
            $birchschedule->plugin_url() . '/assets/js/filedownload/jquery.fileDownload.js',
            array('jquery'), '1.4.0');
    }

    static function register_3rd_styles() {
        global $birchschedule;
        $version = $birchschedule->product_version;

        wp_register_style('fullcalendar_birchpress', 
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

        wp_register_style('bootstrap', 
            $birchschedule->plugin_url() . '/assets/js/bootstrap/css/bootstrap.css', 
            array(), '3.0.3');

        wp_register_style('bootstrap-theme', 
            $birchschedule->plugin_url() . '/assets/js/bootstrap/css/bootstrap-theme.css', 
            array('bootstrap'), '3.0.3');

        wp_deregister_style('select2');
        wp_register_style('select2', 
            $birchschedule->plugin_url() . '/assets/js/select2/select2.css', 
            array(), '3.4.2');

        wp_register_style('jgrowl', 
            $birchschedule->plugin_url() . '/assets/js/jgrowl/jquery.jgrowl.css', 
            array(), '1.2.5');
    }

    static function register_common_scripts() {
        global $birchschedule;
        $version = $birchschedule->product_version;

        wp_register_script('birchpress', 
            $birchschedule->plugin_url() . '/assets/js/birchpress/base.js', 
            array('underscore', 'underscore_string'), "$version");

        wp_register_script('birchpress_util', 
            $birchschedule->plugin_url() . '/assets/js/birchpress/util.js', 
            array('birchpress'), "$version");

        wp_register_script('birchschedule', 
            $birchschedule->plugin_url() . '/assets/js/birchschedule/base.js', 
            array('jquery', 'birchpress', 'birchpress_util'), "$version");

        wp_register_script('birchschedule_model', 
            $birchschedule->plugin_url() . '/assets/js/birchschedule/model/base.js', 
            array('jquery', 'birchpress', 'birchschedule'), "$version");

        wp_register_script('birchschedule_view', 
            $birchschedule->plugin_url() . '/assets/js/birchschedule/view/base.js', 
            array('jquery', 'birchpress', 'birchschedule', 'birchschedule_model'), "$version");

        wp_register_script('birchschedule_view_admincommon', 
            $birchschedule->plugin_url() . '/assets/js/birchschedule/view/admincommon/base.js', 
            array('jquery', 'birchpress', 'birchschedule', 'jgrowl'), "$version");

        wp_register_script('birchschedule_view_clients_edit', 
            $birchschedule->plugin_url() . '/assets/js/birchschedule/view/clients/edit/base.js', 
            array('birchschedule_view_admincommon', 'birchschedule_view'), "$version");

        wp_register_script('birchschedule_view_locations_edit', 
            $birchschedule->plugin_url() . '/assets/js/birchschedule/view/locations/edit/base.js', 
            array('birchschedule_view_admincommon', 'birchschedule_view'), "$version");

        wp_register_script('birchschedule_view_services_edit', 
            $birchschedule->plugin_url() . '/assets/js/birchschedule/view/services/edit/base.js', 
            array('birchschedule_view_admincommon', 'birchschedule_view'), "$version");

        wp_register_script('birchschedule_view_staff_edit', 
            $birchschedule->plugin_url() . '/assets/js/birchschedule/view/staff/edit/base.js', 
            array('birchschedule_view_admincommon', 'birchschedule_view',
                'jscolor'), "$version");

        wp_register_script('birchschedule_view_calendar', 
            $birchschedule->plugin_url() . '/assets/js/birchschedule/view/calendar/base.js', 
            array('birchschedule_view_admincommon', 'birchschedule_view', 
                'fullcalendar_birchpress', 'moment'), "$version");

        wp_register_script('birchschedule_view_bookingform', 
            $birchschedule->plugin_url() . '/assets/js/birchschedule/view/bookingform/base.js', 
            array('jquery-ui-datepicker', 'birchschedule_view'), "$version");

    }

    static function register_common_styles() {
        global $birchschedule;
        $version = $birchschedule->product_version;

        wp_register_style('birchschedule_admincommon', 
            $birchschedule->plugin_url() . '/assets/css/birchschedule/admincommon/base.css', 
            array('jgrowl', 'select2'), "$version");

        wp_register_style('birchschedule_calendar', 
            $birchschedule->plugin_url() . '/assets/css/birchschedule/calendar/base.css', 
            array('jgrowl', 'bootstrap-theme'), "$version");

        wp_register_style('birchschedule_appointments_edit', 
            $birchschedule->plugin_url() . '/assets/css/birchschedule/appointments/edit/base.css', 
            array('jquery-ui-no-theme'), "$version");

        wp_register_style('birchschedule_appointments_new', 
            $birchschedule->plugin_url() . '/assets/css/birchschedule/appointments/new/base.css', 
            array('jquery-ui-no-theme'), "$version");

        wp_register_style('birchschedule_services_edit', 
            $birchschedule->plugin_url() . '/assets/css/birchschedule/services/edit/base.css', 
            array(), "$version");

        wp_register_style('birchschedule_staff_edit', 
            $birchschedule->plugin_url() . '/assets/css/birchschedule/staff/edit/base.css', 
            array(), "$version");

        wp_register_style('birchschedule_locations_edit', 
            $birchschedule->plugin_url() . '/assets/css/birchschedule/locations/edit/base.css', 
            array(), "$version");

        wp_register_style('birchschedule_bookingform', 
            $birchschedule->plugin_url() . '/assets/css/birchschedule/bookingform/base.css',
            array('jquery-ui-no-theme'), "$version");
    }

}
