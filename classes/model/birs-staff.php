<?php

class BIRS_Staff extends BIRS_Model {
    const STEP_LENGTH = 5;

    public function __construct($id, $options) {
        parent::__construct($id, $options);
        $this['post_type'] = 'birs_staff';
    }

    public function get_assigned_service_ids() {
        $assigned_services = $this['_birs_assigned_services'];
        $assigned_services = unserialize($assigned_services);
        $assigned_services = $assigned_services ? $assigned_services : array();
        return $assigned_services;
    }
    
    public function get_schedule_by_location($location_id) {
        $schedule = $this->get_all_schedule();
        $staff_schedule = array();
        if ($location_id) {
            if (isset($schedule[$location_id])) {
                $staff_schedule = $schedule[$location_id];
            }
        }
        return $staff_schedule;
    }
    
    public function get_calculated_schedule_by_location($location_id) {
        $staff_schedule = $this->get_schedule_by_location($location_id);
        $new_schedules = array();
        if(isset($staff_schedule['schedules'])) {
            $schedules = $staff_schedule['schedules'];
            for($week_day = 0; $week_day < 7; $week_day++) {
                $new_schedules[] = array();
            }
            foreach($schedules as $schedule_id => $schedule) {
                $schedule_date_start = 
                    apply_filters('birchschedule_staff_schedule_date_start', '',
                        $this->ID, $location_id, $schedule_id);
                $schedule_date_end = 
                    apply_filters('birchschedule_staff_schedule_date_end', '',
                        $this->ID, $location_id, $schedule_id);
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
        $staff_schedule['schedules'] = $new_schedules;
        return apply_filters('birchschedule_staff_work_schedule_calculated',
            $staff_schedule, $this->ID, $location_id);
    }
    
    public function get_all_calculated_schedule() {
        $all_schedule = $this->get_all_schedule();
        $new_all_schedule = array();
        foreach($all_schedule as $location_id => $schedule) {
            $new_all_schedule[$location_id] = 
                $this->get_calculated_schedule_by_location($location_id);
        }
        return $new_all_schedule;
    }

    private function get_all_schedule() {
        $schedule = $this['_birs_staff_schedule'];
        if (!isset($schedule)) {
            $schedule = array();
        } else {
            $schedule = unserialize($schedule);
        }
        $schedule = $schedule ? $schedule : array();
        return $schedule;
    }
    
    public function get_calendar_import_urls() {
        if($this['_birs_staff_calendar_import_urls']) {
            $calendar_import_urls = 
                unserialize($this['_birs_staff_calendar_import_urls']);
        } else {
            $calendar_import_urls = array();
        }
        return $calendar_import_urls;
    }

    public function get_calendar_import_icals() {
        if($this['_birs_staff_calendar_import_icals']) {
            $calendar_import_icals = 
                json_decode($this['_birs_staff_calendar_import_icals']);
        } else {
            $calendar_import_icals = array();
        }
        return $calendar_import_icals;
    }
    
    private function get_avaliable_schedules_by_date($schedules, $date) {
        $util = BIRS_Util::get_instance();
        $new_schedules = array();
        $mysql_format = "Y-m-d";
        foreach($schedules as $schedule) {
            if($schedule['date_start']) {
                $date_start = $util->get_wp_datetime(
                        array(
                            'date' => $schedule['date_start'],
                            'time' => 0
                        )
                    )->format($mysql_format);
            } else {
                $date_start = "";
            }
            if($schedule['date_end']) {
                $date_end = $util->get_wp_datetime(
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
    
    private function get_exceptions_blocks($exceptions) {
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
            
    private function get_schedules_blocks($schedules) {
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
        
    private function merge_schedules($schedules, $exceptions) {
        $schedules_blocks = $this->get_schedules_blocks($schedules);
        $exceptions_blocks = $this->get_exceptions_blocks($exceptions);
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

    private function get_avaliable_time_options($timeslot, $service, $schedule) {
        $util = BIRS_Util::get_instance();
        $time_options = array();
        $start = $schedule['minutes_start'];
        $end = $schedule['minutes_end'];
        if($timeslot % 5 != 0) {
            $timeslot = (floor($timeslot / 5) + 1) * 5;
        }
        $padding_before = $service->get_padding_before();
        if($padding_before % 5 != 0) {
            $padding_before = (floor($padding_before / 5) + 1) * 5;
        }
        for($i = $start + $padding_before; $i < $end; $i += $timeslot) {
            $time_options[$i] = array(
                'text' => $util->convert_mins_to_time_option($i),
                'avaliable' => true
            );
        }
        $time_options[$end] = array(
            'text' => $util->convert_mins_to_time_option($end),
            'avaliable' => true
        );
        return $time_options;
    }
    
    public function filter_time_options($time_options, $location_id, $service, $date) {
        $util = BIRS_Util::get_instance();
        $staff_id = $this['ID'];
        $service_len = $service->get_service_length();
        $service_padding_after =  $service->get_padding_after();
        $service_padding_before = $service->get_padding_before();
        //filtered by appointments
        $timestamp = $date->format('U');
        $query = new BIRS_Model_Query(
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
                            )
                        )
        );
        $appointments = $query->query();
        $appointments_time = array();
        foreach (array_values($appointments) as $appointment) {
            $busy_time = $appointment['_birs_appointment_timestamp'];
            $datetime = $util->get_wp_datetime($busy_time);
            $busy_time = $util->get_day_minutes($datetime) -
                $appointment['_birs_appointment_padding_before'];
            $appointment_duration = $appointment['_birs_appointment_duration'] + 
                $appointment['_birs_appointment_padding_before'] + 
                $appointment['_birs_appointment_padding_after'];
            $appointments_time[] = array(
                'busy_time' => $busy_time,
                'duration' => $appointment_duration
            );
        }
        $appointments_time = apply_filters('birchschedule_staff_busy_time', 
            $appointments_time, $this->ID, $location_id, $date);
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

    public function get_avaliable_time($location_id, $service_id, $date_us) {
        $util = BIRS_Util::get_instance();
        $staff_id = $this['ID'];
        $date = $util->get_wp_datetime(
                array(
                    'date' => $date_us,
                    'time' => 0
                ));

        $wday = $date->format('w');
        $timeslot = apply_filters('birchschedule_service_timeslot', 15, $service_id);
        $staff_schedule = $this->get_calculated_schedule_by_location($location_id);
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
            $this->get_avaliable_schedules_by_date($day_schedules, $date);
        $avaliable_exceptions = 
            $this->get_avaliable_schedules_by_date($day_exceptions, $date);
        $merged_schedules = 
            $this->merge_schedules($avaliable_schedules, $avaliable_exceptions);
        $all_time_options = array();
        $service = new BIRS_Service($service_id, array(
                    'meta_keys' => array(
                        '_birs_service_length', '_birs_service_length_type',
                        '_birs_service_padding', '_birs_service_padding_type'
                    )
                ));
        $service->load();
        foreach($merged_schedules as $merged_schedule) {
            $time_options = 
                $this->get_avaliable_time_options($timeslot, $service, $merged_schedule);
            $time_options = 
                $this->filter_time_options($time_options, $location_id, $service, $date);
            $all_time_options = $all_time_options + $time_options;
        }
        $all_time_options = apply_filters('birchschedule_booking_time_options', 
            $all_time_options, $service->ID, $date);
        return $all_time_options;
    }

    public function pre_save() {
        
    }
    
    public function post_save() {
        
    }

}

?>
