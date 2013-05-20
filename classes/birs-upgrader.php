<?php

class BIRS_Upgrader {

    function __construct() {
        $this->util = BIRS_Util::get_instance();
        add_action('birchschedule_upgrade_db_core',
            array($this, 'upgrade_core'));
    }
    
    function get_staff_all_schedule_1_0($staff) {
        $schedule = $staff['_birs_staff_schedule'];
        if (!isset($schedule)) {
            $schedule = array();
        } else {
            $schedule = unserialize($schedule);
        }
        $schedule = $schedule ? $schedule : array();
        return $schedule;
    }
    
    function upgrade_staff_schedule_from_1_0_to_1_1(){
        $version = $this->get_staff_schedule_version();
        if($version != '1.0') {
            return;
        }
        $query = new BIRS_Model_Query(
            array(
                'post_type' => 'birs_staff'
            ),
            array(
                'meta_keys' => array(
                    '_birs_staff_schedule'
                )
            )
        );
        $staff = $query->query();
        foreach($staff as $thestaff) {
            $schedules = $this->get_staff_all_schedule_1_0($thestaff);
            $new_all_schedules = array();
            foreach($schedules as $location_id => $schedule) {
                if(isset($schedule['exceptions'])) {
                    $exceptions = $schedule['exceptions'];
                } else {
                    $exceptions = array();
                }
                $new_schedules = array();
                foreach($schedule as $week_day => $day_schedule) {
                    if(isset($day_schedule['enabled'])) {
                        $start = $day_schedule['minutes_start'];
                        $end = $day_schedule['minutes_end'];
                        $new_schedule = array(
                            'minutes_start' => $day_schedule['minutes_start'],
                            'minutes_end' => $day_schedule['minutes_end'],
                            'weeks' => array(
                                $week_day => 'on'
                            )
                        );
                        if(isset($new_schedules['s'. $start. $end])) {
                            $new_schedules['s'. $start. $end]['weeks'][$week_day] = 'on';
                        } else {
                            $new_schedules['s'. $start. $end] = $new_schedule;
                        }
                    }
                }
                $new_loc_schedules = array();
                foreach($new_schedules as $tmp_id => $new_schedule) {
                    $uid = uniqid();
                    $new_loc_schedules[$uid] = $new_schedule;
                }
                $new_all_schedules[$location_id] = array(
                    'schedules' => $new_loc_schedules,
                    'exceptions' => $exceptions
                );
                update_post_meta($thestaff->ID, '_birs_staff_schedule', serialize($new_all_schedules));
            }
        }
        update_option('birs_staff_schedule_version', '1.1');
    }
    
    function get_staff_schedule_version() {
        return get_option('birs_staff_schedule_version', '1.0');
    }
    
    function upgrade_core() {
        $this->upgrade_staff_schedule_from_1_0_to_1_1();
    }
    
    function upgrade() {
        $modules = $this->get_module_names();
        foreach($modules as $module) {
            do_action('birchschedule_upgrade_db_' . $module);
        }
    }

    function get_module_names() {
        global $birchschedule;
        $addons = $birchschedule->addons;
        $modules = array_keys($addons);
        $modules[] = 'core';
        return $modules;
    }
    
    function get_util() {
        return $this->util;
    }

}

?>
