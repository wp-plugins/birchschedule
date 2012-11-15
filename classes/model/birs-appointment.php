<?php

class BIRS_Appointment extends BIRS_Model {

    public function __construct($id, $options) {
        parent::__construct($id, $options);
        $this['post_type'] = 'birs_appointment';
    }

    public function pre_save() {
        $service = new BIRS_Service($this['_birs_appointment_service'], array(
                    'meta_keys' => array(
                        '_birs_service_length', '_birs_service_length_type',
                        '_birs_service_padding', '_birs_service_padding_type'
                    ),
                    'base_keys' => array(
                        'post_title'
                    )
                ));
        $service->load();
        $this['_birs_appointment_duration'] = $service->get_service_length();
        $this['_birs_appointment_padding_before'] = $service->get_padding_before();
        $this['_birs_appointment_padding_after'] = $service->get_padding_after();
        $client = new BIRS_Client($this['_birs_appointment_client'], array(
                    'base_keys' => array(
                        'post_title'
                    )
                ));
        $client->load();
        $this['post_title'] = $service['post_title'] . ' - ' . $client['post_title'];
    }

}

?>
