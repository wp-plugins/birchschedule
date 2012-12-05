<?php

class BIRS_Staff extends BIRS_Model {

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

    public function get_all_schedule($location_id = false) {
        $schedule = $this['_birs_staff_schedule'];
        if (!isset($schedule)) {
            $schedule = array();
        } else {
            $schedule = unserialize($schedule);
        }
        $schedule = $schedule ? $schedule : array();
        $staff_schedule = array();
        if ($location_id) {
            if (isset($schedule[$location_id])) {
                $staff_schedule = $schedule[$location_id];
            }
        } else {
            $staff_schedule = $schedule;
        }
        return $staff_schedule;
    }

    public function get_avaliable_time($location_id, $service_id, $date) {
        $util = BIRS_Util::get_instance();
        $staff_id = $this['ID'];
        $date = $util->get_wp_datetime(
                array(
                    'date' => $date,
                    'time' => 0
                ));

        //filtered by predefined schedule.
        $time_options = $util->get_time_options();
        $wday = $date->format('w');
        $staff_schedule = $this->get_all_schedule($location_id);
        if (isset($staff_schedule[$wday])) {
            $staff_schedule = $staff_schedule[$wday];
        } else {
            return array();
        }
        $time_start = $staff_schedule['minutes_start'];
        $time_end = $staff_schedule['minutes_end'];
        $temp_time_options = array();
        foreach ($time_options as $key => $value) {
            if ($key >= $time_start && $key <= $time_end) {
                $temp_time_options[$key] = array(
                    'text' => $value,
                    'avaliable' => true);
            }
        }
        $time_options = $temp_time_options;

        $service = new BIRS_Service($service_id, array(
                    'meta_keys' => array(
                        '_birs_service_length', '_birs_service_length_type',
                        '_birs_service_padding', '_birs_service_padding_type'
                    )
                ));
        $service->load();
        $service_len = $service->get_service_length() + $service->get_padding_after();
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
                                '_birs_appointment_timestamp', '_birs_appointment_duration', '_birs_appointment_service', '_birs_appointment_padding_before', '_birs_appointment_padding_after'
                            )
                        )
        );
        $appointments = $query->query();
        $appointments_time = array();
        foreach (array_values($appointments) as $appointment) {
            $busy_time = $appointment['_birs_appointment_timestamp'];
            $datetime = $util->get_wp_datetime($busy_time);
            $busy_time = $util->get_day_minutes($datetime) - $appointment['_birs_appointment_padding_before'];
            $appointment_duration = $appointment['_birs_appointment_duration'] + $appointment['_birs_appointment_padding_before'] + $appointment['_birs_appointment_padding_after'];
            $appointments_time[] = array(
                'busy_time' => $busy_time,
                'duration' => $appointment_duration
            );
        }
        $busy_time = end(array_keys($time_options));
        $appointments_time[] = array(
            'busy_time' => $busy_time,
            'duration' => 0
        );
        $busy_time = reset(array_keys($time_options));
        array_unshift($appointments_time, array(
            'busy_time' => $busy_time + $service_padding_before,
            'duration' => 0
        ));
        foreach ($appointments_time as $time) {
            $busy_time = $time['busy_time'];
            $appointment_duration = $time['duration'];
            foreach ($time_options as $key => $value) {
                if ($key > $busy_time - $service_len - $service_padding_before && $key < $busy_time + $appointment_duration + $service_padding_before) {
                    $time_options[$key]['avaliable'] = false;
                }
            }
        }
        $time_options[end(array_keys($time_options))]['avaliable'] = false;
        return $time_options;
    }

}

?>
