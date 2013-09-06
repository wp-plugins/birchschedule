<?php

class BIRS_Addon_General_Settings_Upgrader {
    
    var $default_options;
    var $default_options_1_1;
    var $default_options_1_0;
    var $addon;

    function __construct($addon) {
        $this->addon = $addon;
        $this->default_options_1_0 = array(
            'currency' => 'USD',
            'default_calendar_view' => 'agendaWeek'
        );
        $this->default_options_1_1 = $this->default_options_1_0;
        $this->default_options_1_1['version'] = '1.1';
        $this->default_options = $this->default_options_1_1;
        add_action('birchschedule_upgrade_db_general_settings', array($this, 'upgrade'));
    }

    function upgrade() {
        $this->init();
        $this->upgrade_1_0_to_1_1();
        do_action('birchschedule_upgrade_db_general_settings_after');
    }

    function get_db_version_options() {
        $options = get_option('birchschedule_options');
        if(isset($options['version'])) {
            return $options['version'];
        } else {
            return '1.0';
        }
    }

    function upgrade_1_0_to_1_1() {
        $version = $this->get_db_version_options();
        if($version !== '1.0') {
            return;
        }
        $options = get_option('birchschedule_options');
        $options['version'] = '1.1';
        update_option('birchschedule_options', $options);
    }
    
    function init() {
        $options = get_option('birchschedule_options');
        if($options === false) {
            add_option('birchschedule_options', $this->default_options);
        }
    }

}
?>
