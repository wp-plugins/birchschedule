<?php

final class Birchpress_View_Imp {

    private static $birchpress;

    private static $package;

    private static $scripts_data;

    private static $enqueued_scripts;

    private static $localized_scripts;

    private static $printed_scripts;

    private function __construct() {
    }

    static function init_vars() {
        global $birchpress;

        self::$birchpress = $birchpress;

        self::$package = $birchpress->view;

        self::$scripts_data = array();

        self::$enqueued_scripts = array();

        self::$localized_scripts = array();

        self::$printed_scripts = array();
    }

    static function init() {
        add_action('init', array(self::$package, 'wp_init'));

        add_action('admin_init', array(self::$package, 'wp_admin_init'));
    }

    static function wp_init() {
        if(!defined('DOING_AJAX')) {

            self::$package->register_core_scripts();

            add_action('wp_print_scripts', 
                array(self::$package, 'localize_scripts'));

            if(is_admin()) {
                add_action('admin_print_footer_scripts', 
                    array(self::$package, 'localize_scripts'), 9);
                add_action('admin_print_footer_scripts', 
                    array(self::$package, 'post_print_scripts'), 11);
            } else {
                add_action('wp_print_footer_scripts',
                    array(self::$package, 'localize_scripts'), 9);
                add_action('wp_print_footer_scripts',
                    array(self::$package, 'post_print_scripts'), 11);
            }
        }
    }

    static function wp_admin_init() {
        add_action('load-post.php', array(__CLASS__, 'on_load_post'));
        add_action('load-post-new.php', array(__CLASS__, 'on_load_post_new'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'on_admin_enqueue_scripts'));
        add_action('save_post', array(__CLASS__, 'on_save_post'), 10, 2);
        add_filter('wp_insert_post_data', array(__CLASS__, 'apply_pre_save_post'), 10, 2);
    }

    static function register_3rd_scripts() {
        global $birchpress;

        wp_register_script('underscore_string', 
            $birchpress->get_framework_url() . '/lib/assets/js/underscore/underscore.string.min.js', 
            array('underscore'), '2.3.0');
    }

    static function register_core_scripts() {
        global $birchpress;

        $version = $birchpress->get_version();
        wp_register_script('birchbase', 
            $birchpress->get_framework_url() . '/assets/js/birchbase/package.js', 
            array('underscore', 'underscore_string'), "$version");

        wp_register_script('birchpress', 
            $birchpress->get_framework_url() . '/assets/js/birchpress/base.js', 
            array('birchbase'), "$version");

        wp_register_script('birchpress_util', 
            $birchpress->get_framework_url() . '/assets/js/birchpress/util/base.js', 
            array('birchpress'), "$version");
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

        $post_a = (array)$post;
        self::$package->save_post($post_a);
    }

    static function pre_save_post($post_data, $post_attr) { return $post_data; }

    static function apply_pre_save_post($post_data, $post_attr) {
        if(!isset($_POST['action']) || $_POST['action'] !== 'editpost') {
            return $post_data;
        }

        if($post_data['post_status'] == 'auto-draft') {
            return $post_data;
        }
        return self::$package->pre_save_post($post_data, $post_attr);
    }

    static function on_load_post() {
        $post_type = self::$package->get_current_post_type();
        self::$package->load_post_edit(array(
            'post_type' => $post_type
        ));
    }

    static function on_load_post_new() {
        $post_type = self::$package->get_current_post_type();
        self::$package->load_post_new(array(
            'post_type' => $post_type
        ));
    }

    static function load_post_new($arg) {}

    static function load_post_edit($arg) {}

    static function get_current_post_type() {
        global $current_screen;

        if( $current_screen && $current_screen->post_type ) {
            return $current_screen->post_type;
        }

        return '';
    }

    static function on_admin_enqueue_scripts($hook) {
        $post_type = self::$package->get_current_post_type();
        if($hook == 'post-new.php') {
            self::$package->enqueue_scripts_post_new(array(
                'post_type' => $post_type
            ));
        }
        if($hook == 'post.php') {
            self::$package->enqueue_scripts_post_edit(array(
                'post_type' => $post_type
            ));
        }
        if($hook == 'edit.php' && isset($_GET['post_type'])) {
            $post_type = $_GET['post_type'];
            self::$package->enqueue_scripts_post_list(array(
                'post_type' => $post_type
            ));
        }
    }

    static function enqueue_scripts_post_new($arg) {}

    static function enqueue_scripts_post_edit($arg) {}

    static function enqueue_scripts_post_list($arg) {}

    static function register_script_data_fn($handle, $data_name, $fn) {
        if(isset(self::$scripts_data[$handle])) {
            self::$scripts_data[$handle][$data_name] = $fn;
        } else {
            self::$scripts_data[$handle] = array(
                $data_name => $fn
            );
        }
    }

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

    static function localize_scripts() {
        global $wp_scripts;

        $wp_scripts->all_deps(self::$enqueued_scripts, true);
        $all_scripts = $wp_scripts->to_do;

        foreach($all_scripts as $script) {
            self::$package->localize_script($script);
        }
        self::$printed_scripts = $all_scripts;
    }

    static function localize_script($script) {
        $scripts_data = self::$scripts_data;

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

    static function post_print_scripts() {
        foreach(self::$printed_scripts as $script) {
            self::$package->post_print_script($script);
        }
    }

    static function post_print_script($script) {}

    static function get_screen($hook_name) {
        global $birchbase;
        
        return $birchbase->view->get_wp_screen($hook_name);
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
        return http_build_query(self::$package->get_query_array($query, $keys));
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
        if(self::$birchpress->util->is_errors($errors)) {
            $error_arr = array();
            $codes = self::$birchpress->util->get_error_codes($errors);
            foreach($codes as $code) {
                $error_arr[$code] = self::$birchpress->util->get_error_message($errors, $code);
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

}

Birchpress_View_Imp::init_vars();

?>
