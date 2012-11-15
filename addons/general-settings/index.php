<?php

class BIRS_Addon_Settings_General extends BIRS_Addon {

    function __construct() {
        parent::__construct();
        add_action('admin_init', array($this, 'init'));
        add_filter('birchschedule_settings_tabs', array(&$this, 'add_tab'));
        add_filter('birchschedule_price_label', array($this, 'add_currency_symbol'));
        add_filter('birchschedule_price', array($this, 'add_currency_symbol'));
        add_filter('birchschedule_default_calendar_view', array($this, 'get_option_default_calendar_view'));
    }

    function get_addon_dir_name() {
        return 'general-settings';
    }

    function init() {
        register_setting('birchschedule_options', 'birchschedule_options');

        add_settings_section('birchschedule_general', __('General Options', 'birchschedule'), array(&$this, 'render_section_general'), 'birchschedule_settings');

        add_settings_field('birchschedule_timezone', __('Timezone'), array(&$this, 'render_timezone'), 'birchschedule_settings', 'birchschedule_general');
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

    function render_timezone() {
        $timezone_url = admin_url('options-general.php');
        echo "<label>Timezone settings are located <a href='$timezone_url'>here</a>.</label>";
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

            <input name="Submit" type="submit" class="button-primary"
                   value="<?php _e('Save changes', 'birchschedule'); ?>" />
        </form>
        <?php
    }

}

$birchschedule->addons['general_settings'] = new BIRS_Addon_Settings_General();
?>
