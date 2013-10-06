<?php

class Birchschedule_Model_Schedule_Imp {
    const STEP_LENGTH = 5;

	private function __construct() {

	}

    static function get_staff_calculated_schedule_by_location($staff_id, $location_id) {
        global $birchschedule;

        $location_schedule = $birchschedule->model->
            get_staff_schedule_by_location($staff_id, $location_id);
        $new_schedules = array();
        if(isset($location_schedule['schedules'])) {
            $schedules = $location_schedule['schedules'];
            for($week_day = 0; $week_day < 7; $week_day++) {
                $new_schedules[] = array();
            }
            foreach($schedules as $schedule_id => $schedule) {
                $schedule_date_start = 
                    $birchschedule->model->
                        get_staff_schedule_date_start($staff_id, $location_id, $schedule_id);
                $schedule_date_end = 
                    $birchschedule->model->
                        get_staff_schedule_date_end($staff_id, $location_id, $schedule_id);
                foreach($new_schedules as $week_day => $new_schedule) {
                    if(isset($schedule['weeks'][$week_day])) {
                        $new_schedules[$week_day][] = array(
                            'minutes_start' => $schedule['minutes_start'],
                            'minutes_end' => $schedule['minutes_end'],
                            'date_start' => $schedule_date_start,
                            'date_end' => $schedule_date_end
                        );
                    }
                }
            }
        }
        $location_schedule['schedules'] = $new_schedules;
        return $location_schedule;
    }

    static function get_staff_calculated_schedule($staff_id) {
        global $birchschedule;
        
        $staff = $birchschedule->model->get($staff_id, array(
                    'base_keys' => array(),
                    'meta_keys' => array('_birs_staff_schedule')
                ));
        $staff_schedule = $staff['_birs_staff_schedule'];
        $new_all_schedule = array();
        foreach($staff_schedule as $location_id => $schedule) {
            $new_all_schedule[$location_id] = 
                $birchschedule->model->
                    get_staff_calculated_schedule_by_location($staff_id, $location_id);
        }
        return $new_all_schedule;
    }

    static function get_all_calculated_schedule() {
        global $birchschedule;

        $staff = $birchschedule->model->query(
                        array(
                            'post_type' => 'birs_staff'
                        ),
                        array(
                            'meta_keys' => array(),
                            'base_keys' => array()
                        )
                    );
        $allschedule = array();
        foreach ($staff as $thestaff) {
            $schedule = $birchschedule->model->get_staff_calculated_schedule($thestaff['ID']);
            $allschedule[$thestaff['ID']] = $schedule;
        }
        return $allschedule;
    }

    static function get_staff_schedule_date_start(
        $staff_id, $location_id, $schedule_id) {

        return '';
    }
    
    static function get_staff_schedule_date_end(
        $staff_id, $location_id, $schedule_id) {

        return '';
    }

    static function get_staff_exception_date_start(
        $staff_id, $location_id, $exception_id) {

        return '';
    }
    
    static function get_staff_exception_date_end(
        $staff_id, $location_id, $exception_id) {

        return '';
    }

    private static function get_avaliable_schedules_by_date($schedules, $date) {
        global $birchpress;

        $new_schedules = array();
        $mysql_format = "Y-m-d";
        foreach($schedules as $schedule) {
            if($schedule['date_start']) {
                $date_start = $birchpress->util->get_wp_datetime(
                        array(
                            'date' => $schedule['date_start'],
                            'time' => 0
                        )
                    )->format($mysql_format);
            } else {
                $date_start = "";
            }
            if($schedule['date_end']) {
                $date_end = $birchpress->util->get_wp_datetime(
                        array(
                            'date' => $schedule['date_end'],
                            'time' => 0
                        )
                    )->format($mysql_format);
            } else {
                $date_end = 'a';
            }
            $current_date = $date->format($mysql_format);
            if($date_start <= $current_date &&
                $date_end >= $current_date) {
                $new_schedules[] = array(
                    'minutes_start' => $schedule['minutes_start'],
                    'minutes_end' => $schedule['minutes_end']
                );
            }
        }
        return $new_schedules;
    }
    
    private static function get_exceptions_blocks($exceptions) {
        $exceptions_blocks = array();
        foreach($exceptions as $exception) {
            $start = $exception['minutes_start'];
            $end = $exception['minutes_end'];
            $exception_blocks = array();
            for($i = $start + self::STEP_LENGTH; $i < $end; $i += self::STEP_LENGTH) {
                $exception_blocks[] = $i;
            }
            $exceptions_blocks = 
                array_unique(array_merge($exceptions_blocks, $exception_blocks));
        }
        return $exceptions_blocks;
    }
            
    private static function get_schedules_blocks($schedules) {
        $schedules_blocks = array();
        foreach($schedules as $schedule) {
            $start = $schedule['minutes_start'];
            $end = $schedule['minutes_end'];
            $schedule_blocks = array();
            for($i = $start; $i <= $end; $i += self::STEP_LENGTH) {
                $schedule_blocks[] = $i;
            }
            $schedules_blocks = 
                array_unique(array_merge($schedules_blocks, $schedule_blocks));
        }
        return $schedules_blocks;
    }
        
    private static function merge_schedules($schedules, $exceptions) {
        $schedules_blocks = self::get_schedules_blocks($schedules);
        $exceptions_blocks = self::get_exceptions_blocks($exceptions);
        $all_blocks = array();
        $max_step  = 1440 / self::STEP_LENGTH;
        for($i = 0; $i < $max_step; $i ++) {
            $block = $i * self::STEP_LENGTH;
            if(in_array($block, $schedules_blocks)) {
                $all_blocks[$block] = true;
            } else {
                $all_blocks[$block] = false;
            }
            if(in_array($block, $exceptions_blocks)) {
                $all_blocks[$block] = false;
            }        
        }
        $all_blocks[1440] = false;
        $merged = array();
        $started = false;
        foreach($all_blocks as $block => $block_value) {
            if(!$started && $block_value) {
                $new_schedule = array(
                    'minutes_start' => $block
                );
                $started = true;
            }
            if($started && $block_value) {
                $new_schedule['minutes_end'] = $block;
            }
            if($started && !$block_value) {
                $started = false;
                $merged[] = $new_schedule;
            }
        }
        return $merged;
    }

    static function get_staff_busy_time($staff_id, $location_id, $date) {
        global $birchschedule, $birchpress;

        $timestamp = $date->format('U');
        $appointments = $birchschedule->model->query(
            array(
                'post_type' => 'birs_appointment',
                'meta_query' => array(
                    array(
                        'key' => '_birs_appointment_timestamp',
                        'value' => array($timestamp, $timestamp + 3600 * 24),
                        'type' => 'numeric',
                        'compare' => 'BETWEEN'
                    ),
                    array(
                        'key' => '_birs_appointment_staff',
                        'value' => $staff_id
                    )
                )
            ),
            array(
                'meta_keys' => array(
                    '_birs_appointment_timestamp', '_birs_appointment_duration',
                    '_birs_appointment_service', '_birs_appointment_padding_before',
                    '_birs_appointment_padding_after'
                ),
                'base_keys' => array()
            )
        );
        $appointments_time = array();
        foreach ($appointments as $appointment) {
            $busy_time = $appointment['_birs_appointment_timestamp'];
            $datetime = $birchpress->util->get_wp_datetime($busy_time);
            $busy_time = $birchpress->util->get_day_minutes($datetime) -
                $appointment['_birs_appointment_padding_before'];
            $appointment_duration = $appointment['_birs_appointment_duration'] + 
                $appointment['_birs_appointment_padding_before'] + 
                $appointment['_birs_appointment_padding_after'];
            $appointments_time[] = array(
                'busy_time' => $busy_time,
                'duration' => $appointment_duration
            );
        }
        return $appointments_time;
    }

    private static function filter_time_options($time_options, $staff_id, $location_id, $service_id, $date) {
        global $birchpress, $birchschedule;

        $service_len = $birchschedule->model->get_service_length($service_id);
        $service_padding_after = $birchschedule->model->get_service_padding_after($service_id);
        $service_padding_before = $birchschedule->model->get_service_padding_before($service_id);
        //filtered by appointments
        $appointments_time = $birchschedule->model->get_staff_busy_time($staff_id, $location_id, $date);
        $busy_time = end(array_keys($time_options));
        $appointments_time[] = array(
            'busy_time' => $busy_time,
            'duration' => 0
        );
        foreach ($appointments_time as $time) {
            $busy_time = $time['busy_time'];
            $appointment_duration = $time['duration'];
            foreach ($time_options as $key => $value) {
                if ($key > $busy_time - $service_padding_after - $service_len
                    && $key < $busy_time + $appointment_duration + 
                    $service_padding_before) {
                    $time_options[$key]['avaliable'] = false;
                }
            }
        }
        $time_options[end(array_keys($time_options))]['avaliable'] = false;
        return $time_options;
    }

    private static function get_avaliable_time_options($timeslot, $padding_before, $schedule) {
        global $birchpress;

        $time_options = array();
        $start = $schedule['minutes_start'];
        $end = $schedule['minutes_end'];
        if($timeslot % 5 != 0) {
            $timeslot = (floor($timeslot / 5) + 1) * 5;
        }
        if($padding_before % 5 != 0) {
            $padding_before = (floor($padding_before / 5) + 1) * 5;
        }
        for($i = $start + $padding_before; $i < $end; $i += $timeslot) {
            $time_options[$i] = array(
                'text' => $birchpress->util->convert_mins_to_time_option($i),
                'avaliable' => true
            );
        }
        $time_options[$end] = array(
            'text' => $birchpress->util->convert_mins_to_time_option($end),
            'avaliable' => true
        );
        return $time_options;
    }
    
    static function get_staff_avaliable_time($staff_id, $location_id, 
        $service_id, $date) {
        
        global $birchschedule;

        $wday = $date->format('w');
        $timeslot = $birchschedule->model->get_service_timeslot($service_id);
        $padding_before = $birchschedule->model->get_service_padding_before($service_id);
        $staff_schedule = $birchschedule->model->get_staff_calculated_schedule_by_location($staff_id, $location_id);
        if (isset($staff_schedule['schedules'][$wday])) {
            $day_schedules = $staff_schedule['schedules'][$wday];
        } else {
            return array();
        }
        if (isset($staff_schedule['exceptions'][$wday])) {
            $day_exceptions = $staff_schedule['exceptions'][$wday];
        } else {
            $day_exceptions = array();
        }
        $avaliable_schedules = 
            self::get_avaliable_schedules_by_date($day_schedules, $date);
        $avaliable_exceptions = 
            self::get_avaliable_schedules_by_date($day_exceptions, $date);
        $merged_schedules = 
            self::merge_schedules($avaliable_schedules, $avaliable_exceptions);
        $all_time_options = array();
        foreach($merged_schedules as $merged_schedule) {
            $time_options = 
                self::get_avaliable_time_options($timeslot, $padding_before, $merged_schedule);
            $time_options = 
                self::filter_time_options($time_options, $staff_id, $location_id, $service_id, $date);
            $all_time_options = $all_time_options + $time_options;
        }
        return $all_time_options;
    }

}