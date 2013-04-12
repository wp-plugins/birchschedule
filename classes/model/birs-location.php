<?php

class BIRS_Location extends BIRS_Model {

    public function __construct($id, $options) {
        parent::__construct($id, $options);
        $this['post_type'] = 'birs_location';
    }

    public function get_assigned_staff() {
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_staff'
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
            if (sizeof($schedule['schedules']) > 0) {
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
