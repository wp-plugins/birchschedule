<?php

class BIRS_Location extends BIRS_Model {

    public function __construct($id, $options) {
        parent::__construct($id, $options);
        $this['post_type'] = 'birs_location';
    }
    
    public function get_assigned_services() {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_service',
                            'order' => 'ASC',
                            'orderby' => 'title'
                        ),
                        array(
                            'meta_keys' => array(
                                '_birs_service_length', '_birs_service_length_type',
                                '_birs_service_price', '_birs_service_price_type',
                                '_birs_service_assigned_locations'
                            ),
                            'base_keys' => array(
                                'post_title'
                            )
                        )
        );
        $assigned_services = array();
        $services = $query->query();
        foreach($services as $service) {
            $assigned_locations = $service->_birs_service_assigned_locations;
            if($assigned_locations) {
                $assigned_locations = unserialize($assigned_locations);
                if(isset($assigned_locations[$this->ID])) {
                    $assigned_services[$service->ID] = 
                        apply_filters('birchschedule_service_option_text', 
                            "", $service);
                }
            }
        }
        return $assigned_services;
    }

    public function get_assigned_staff() {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_staff',
                            'order' => 'ASC',
                            'orderby' => 'title'
                        ),
                        array(
                            'meta_keys' => array(
                                '_birs_staff_schedule'
                            ),
                            'base_keys' => array(
                                'post_title'
                            )
                        )
        );
        $assigned_staff = array();
        $staff = $query->query();
        foreach ($staff as $the_staff) {
            $schedule = $the_staff->get_schedule_by_location($this->ID);
            if (isset($schedule['schedules']) && 
                sizeof($schedule['schedules']) > 0) {
                $assigned_staff[$the_staff->ID] = $the_staff->post_title;
            }
        }
        return $assigned_staff;
    }
    
    public function pre_save() {
        
    }
    
    public function post_save() {
        
    }

}

?>
