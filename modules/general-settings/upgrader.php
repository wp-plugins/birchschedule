<?php

final class Birchschedule_Gsettings_Upgrader {

    private static $default_options;
    private static $default_options_1_1;
    private static $default_options_1_0;

    static function upgrade_module() {
        self::init();
        self::upgrade_1_0_to_1_1();
    }

    static function get_db_version_options() {
        $options = get_option('birchschedule_options');
        if(isset($options['version'])) {
            return $options['version'];
        } else {
            return '1.0';
        }
    }

    static function upgrade_1_0_to_1_1() {
        $version = self::get_db_version_options();
        if($version !== '1.0') {
            return;
        }
        $options = get_option('birchschedule_options');
        $options['version'] = '1.1';
        update_option('birchschedule_options', $options);
    }

    static function init_constants() {
        self::$default_options_1_0 = array(
            'currency' => 'USD',
            'default_calendar_view' => 'agendaWeek'
        );
        self::$default_options_1_1 = self::$default_options_1_0;
        self::$default_options_1_1['version'] = '1.1';
        self::$default_options = self::$default_options_1_1;
    }
    
    static function init() {
    	self::init_constants();
        $options = get_option('birchschedule_options');
        if($options === false) {
            add_option('birchschedule_options', self::$default_options);
        }
    }
}