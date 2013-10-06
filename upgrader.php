<?php

class Birchschedule_Upgrader {

    private function __construct() {
    }
    
    private static function get_staff_all_schedule_1_0($staff) {
        $schedule = $staff['_birs_staff_schedule'];
        if (!isset($schedule)) {
            $schedule = array();
        } else {
            $schedule = unserialize($schedule);
        }
        $schedule = $schedule ? $schedule : array();
        return $schedule;
    }
    
    private static function upgrade_staff_schedule_from_1_0_to_1_1(){
        global $birchpress;

        $version = self::get_staff_schedule_version();
        if($version != '1.0') {
            return;
        }
        $staff = $birchpress->db->query(
            array(
                'post_type' => 'birs_staff'
            ),
            array(
                'meta_keys' => array(
                    '_birs_staff_schedule'
                )
            )
        );
        foreach($staff as $thestaff) {
            $schedules = self::get_staff_all_schedule_1_0($thestaff);
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
                update_post_meta($thestaff['ID'], '_birs_staff_schedule', serialize($new_all_schedules));
            }
        }
        update_option('birs_staff_schedule_version', '1.1');
    }
    
    private static function get_staff_schedule_version() {
        return get_option('birs_staff_schedule_version', '1.0');
    }

    public static function upgrade_core() {
        self::upgrade_staff_schedule_from_1_0_to_1_1();
    }

}

?>
