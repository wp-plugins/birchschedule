<?php

final class Birchschedule_Gsettings_Imp {
    
    private static $update_info = array();
    var $upgrader;

    static function init() {
        global $birchschedule;

        $package = $birchschedule->gsettings;
        add_action('init', array(__CLASS__, 'wp_init'));
        add_action('admin_init', array(__CLASS__, 'wp_admin_init'));
    }
    
    static function wp_init() {
        add_action('birchschedule_view_show_notice', array(__CLASS__, 'show_update_notice'));
        add_filter('site_transient_update_plugins', array(__CLASS__, 'get_update_info'), 20);
        add_filter('birchschedule_view_settings_get_tabs', array(__CLASS__, 'add_tab'));
        add_filter('birchschedule_model_get_currency_code', array(__CLASS__, 'get_option_currency'));
        add_filter('birchschedule_view_calendar_get_default_view', 
            array(__CLASS__, 'get_option_default_calendar_view'));
    }
    
    static function wp_admin_init() {
        global $birchschedule;

        $package = $birchschedule->gsettings;
        register_setting('birchschedule_options', 'birchschedule_options', array($package, 'sanitize_input'));
        $package->add_settings_sections();
    }
    
    static function add_tab($tabs) {
        global $birchschedule;

        $package = $birchschedule->gsettings;
        $tabs['general'] = array(
            'title' => __('General', 'birchschedule'),
            'action' => array($package, 'render_page'),
            'order' => 0
        );

        return $tabs;
    }

    static function add_settings_sections() {
        global $birchschedule;

        $package = $birchschedule->gsettings;
        add_settings_section('birchschedule_general', __('General Options', 'birchschedule'), 
            array(__CLASS__, 'render_section_general'), 'birchschedule_settings');
        $package->add_settings_fields();
    }

    static function add_settings_fields() {
        add_settings_field('birchschedule_timezone', __('Timezone'), 
            array(__CLASS__, 'render_timezone'), 'birchschedule_settings', 'birchschedule_general');
        add_settings_field('birchschedule_date_time_format', __('Date Format, Time Format', 'birchschedule'), 
            array(__CLASS__, 'render_date_time_format'), 'birchschedule_settings', 'birchschedule_general');
        add_settings_field('birchschedule_start_of_week', __('Week Starts On', 'birchschedule'), 
            array(__CLASS__, 'render_start_of_week'), 'birchschedule_settings', 'birchschedule_general');
        add_settings_field('birchschedule_currency', __('Currency', 'birchschedule'), 
            array(__CLASS__, 'render_currency'), 'birchschedule_settings', 'birchschedule_general');
        add_settings_field('birchschedule_default_calendar_view', __('Default Calendar View', 'birchschedule'), 
            array(__CLASS__, 'render_default_calendar_view'), 'birchschedule_settings', 'birchschedule_general');
    }

    static function get_option_currency() {
        $options = self::get_options();
        return $options['currency'];
    }

    static function get_option_default_calendar_view() {
        $options = self::get_options();
        return $options['default_calendar_view'];
    }

    static function render_section_general() {
        echo '';
    }
    
    static function get_options() {
        $options = get_option('birchschedule_options');
        return $options;
    }

    static function render_timezone() {
        $timezone_url = admin_url('options-general.php');
        echo sprintf(
            __("<label>Timezone settings are located <a href='%s'>here</a>.</label>", 'birchschedule'), 
            $timezone_url);
    }
    
    static function render_date_time_format() {
        $timezone_url = admin_url('options-general.php');
        echo sprintf(
            __("<label>Date format, time format settings are located <a href='%s'>here</a>.</label>", 'birchschedule'),
            $timezone_url);
    }
    
    static function render_start_of_week() {
        $timezone_url = admin_url('options-general.php');
        echo sprintf(
            __("<label>First day of week setting is located <a href='%s'>here</a>.</label>", 'birchschedule'), 
            $timezone_url);
    }
    
    static function map_currencies($currency) {
        if ($currency['symbol_right'] != '') {
            return $currency['title'] . ' (' . $currency['symbol_right'] . ')';
        } else {
            return $currency['title'] . ' (' . $currency['symbol_left'] . ')';
        }
    }

    static function render_currency() {
        global $birchpress;

        $currencies = $birchpress->util->get_currencies();
        $currencies = array_map(array(__CLASS__, 'map_currencies'), $currencies);
        $currency = self::get_option_currency();
        echo '<select id="birchschedule_currency" name="birchschedule_options[currency]">';
        $birchpress->util->render_html_options($currencies, $currency);
        echo '</select>';
    }

    static function render_default_calendar_view() {
        global $birchpress;

        $views = $birchpress->util->get_calendar_views();
        $default_view = self::get_option_default_calendar_view();
        echo '<select id="birchschedule_default_calenar_view" name="birchschedule_options[default_calendar_view]">';
        $birchpress->util->render_html_options($views, $default_view);
        echo '</select>';
    }

    static function render_page() {
        $options = self::get_options();
        $version = $options['version'];
        settings_errors();
        ?>
        <form action="options.php" method="post">
            <input type='hidden' name='birchschedule_options[version]' value='<?php echo $version; ?>'>
            <?php settings_fields('birchschedule_options'); ?>
            <?php do_settings_sections('birchschedule_settings'); ?>
            <p class="submit">
                <input name="Submit" type="submit" class="button-primary"
                       value="<?php _e('Save changes', 'birchschedule'); ?>" />
            </p>
        </form>
        <?php
    }

    static function sanitize_input($input) {
        return $input;
    }
    
    static function get_update_info($checked_data) {
        $plugin_slug = "birchschedule";
        $slug_str = $plugin_slug . '/' . $plugin_slug . '.php';
        if(isset($checked_data->response[$slug_str])) {
            $update_info = $checked_data->response[$slug_str];
            self::$update_info = array(
                'version' => $update_info->new_version
            );
        }
        return $checked_data;
    }
    
    static function show_update_notice() {
        global $birchschedule;
        $product_name = $birchschedule->product_name;
        $update_url = admin_url('update-core.php');
        $update_text = "%s %s is available! <a href='$update_url'>Please update now</a>.";
        if(self::$update_info):
    ?>
        <div class="updated inline">
            <p><?php echo sprintf($update_text, $product_name, self::$update_info['version']); ?></p>
        </div>
        <?php
        endif;
    }

}
?>
