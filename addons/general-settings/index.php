<?php

class BIRS_Addon_Settings_General extends BIRS_Addon {
    
    var $default_options;
    var $update_info = array();

    function __construct() {
        parent::__construct();
        add_action('admin_init', array($this, 'init'));
        add_filter('birchschedule_settings_tabs', array(&$this, 'add_tab'));
        add_filter('birchschedule_price_label', array($this, 'add_currency_symbol'));
        add_filter('birchschedule_price', array($this, 'add_currency_symbol'));
        add_filter('birchschedule_default_calendar_view', array($this, 'get_option_default_calendar_view'));
        add_action('birchschedule_show_update_notice', array($this, 'show_update_notice'));
        add_filter('site_transient_update_plugins', array($this, 'get_update_info'), 20);
        $this->init_constants();
    }
    
    function init_constants() {
        $this->default_options = array(
            'currency' => 'USD',
            'default_calendar_view' => 'agendaWeek'
        );
    }
    
    function get_addon_dir_name() {
        return 'general-settings';
    }

    function init() {
        register_setting('birchschedule_options', 'birchschedule_options', array($this, 'sanitize_input'));
        add_settings_section('birchschedule_general', __('General Options', 'birchschedule'), array(&$this, 'render_section_general'), 'birchschedule_settings');
        do_action('birchschedule_add_settings_section_general_after');
        add_settings_field('birchschedule_timezone', __('Timezone'), array(&$this, 'render_timezone'), 'birchschedule_settings', 'birchschedule_general');
        add_settings_field('birchschedule_date_time_format', __('Date Format, Time Format'), array(&$this, 'render_date_time_format'), 'birchschedule_settings', 'birchschedule_general');
        add_settings_field('birchschedule_currency', __('Currency'), array(&$this, 'render_currency'), 'birchschedule_settings', 'birchschedule_general');
        add_settings_field('birchschedule_default_calendar_view', __('Default Calendar View'), array(&$this, 'render_default_calendar_view'), 'birchschedule_settings', 'birchschedule_general');
    }

    function add_tab($tabs) {
        $tabs['general'] = array(
            'title' => __('General', 'birchschedule'),
            'action' => array(&$this, 'render_page'),
            'order' => 0
        );

        return $tabs;
    }

    function add_currency_symbol($arg) {
        $currencies = $this->util->get_currencies();
        $currency = $this->get_option_currency();
        $currency = $currencies[$currency];
        $symbol = $currency['symbol_right'];
        if ($symbol == '') {
            $symbol = $currency['symbol_left'];
        }
        if (is_numeric($arg)) {
            if ($currency['symbol_right']) {
                $arg .= $symbol;
            } else {
                $arg = $symbol . $arg;
            }
        } else {
            $arg = $arg . ' (' . $symbol . ')';
        }

        return $arg;
    }

    function get_option_currency($currency = 'USD') {
        $options = get_option('birchschedule_options');
        if (isset($options['currency'])) {
            $currency = $options['currency'];
        }
        return $currency;
    }

    function get_option_default_calendar_view($view = 'agendaWeek') {
        $options = get_option('birchschedule_options');
        if (isset($options['default_calendar_view'])) {
            $view = $options['default_calendar_view'];
        }
        return $view;
    }

    function render_section_general() {
        echo '';
    }
    
    function get_options() {
        $options = get_option('birchschedule_options');
        if($options === false) {
            add_option('birchschedule_options', $this->default_options);
            return $this->default_options;
        }
        return $options;
    }

    function render_timezone() {
        $timezone_url = admin_url('options-general.php');
        echo sprintf(
            __("<label>Timezone settings are located <a href='%s'>here</a>.</label>", 'birchschedule'), 
            $timezone_url);
    }
    
    function render_date_time_format() {
        $timezone_url = admin_url('options-general.php');
        echo sprintf(
            __("<label>Date format, time format settings are located <a href='%s'>here</a>.</label>", 'birchschedule'),
            $timezone_url);
    }
    
    function map_currencies($currency) {
        if ($currency['symbol_right'] != '') {
            return $currency['title'] . ' (' . $currency['symbol_right'] . ')';
        } else {
            return $currency['title'] . ' (' . $currency['symbol_left'] . ')';
        }
    }

    function render_currency() {
        $currencies = $this->util->get_currencies();
        $currencies = array_map(array(&$this, 'map_currencies'), $currencies);
        $currency = $this->get_option_currency();
        echo '<select id="birchschedule_currency" name="birchschedule_options[currency]">';
        $this->util->render_html_options($currencies, $currency);
        echo '</select>';
    }

    function render_default_calendar_view() {
        $views = $this->util->get_calendar_views();
        $default_view = $this->get_option_default_calendar_view();
        echo '<select id="birchschedule_default_calenar_view" name="birchschedule_options[default_calendar_view]">';
        $this->util->render_html_options($views, $default_view);
        echo '</select>';
    }

    function render_page() {
        settings_errors();
        ?>
        <form action="options.php" method="post">
            <?php settings_fields('birchschedule_options'); ?>
            <?php do_settings_sections('birchschedule_settings'); ?>
            <p class="submit">
                <input name="Submit" type="submit" class="button-primary"
                       value="<?php _e('Save changes', 'birchschedule'); ?>" />
            </p>
        </form>
        <?php
    }

    function sanitize_input($input) {
        return apply_filters('birchschedule_sanitize_options_input', $input);
    }
    
    function get_update_info($checked_data) {
        $plugin_slug = "birchschedule";
        $slug_str = $plugin_slug . '/' . $plugin_slug . '.php';
        if(isset($checked_data->response[$slug_str])) {
            $this->update_info = array(
                'version' => $checked_data->response[$slug_str]->version
            );
        }
        return $checked_data;
    }
    
    function show_update_notice() {
        global $birchschedule;
        $product_name = $birchschedule->product_name;
        $update_url = admin_url('update-core.php');
        $update_text = "%s %s is available! <a href='$update_url'>Please update now</a>.";
        if($this->update_info):
    ?>
        <div class="updated inline">
            <p><?php echo sprintf($update_text, $product_name, $this->update_info['version']); ?></p>
        </div>
        <?php
        endif;
    }

}

$birchschedule->addons['general_settings'] = new BIRS_Addon_Settings_General();
?>
