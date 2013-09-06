<?php

class BIRS_Appointment extends BIRS_Model {
    
    public function __construct($id, $options=array()) {
        parent::__construct($id, $options);
        if(!in_array('post_status', $this->base_keys)) {
            $this->base_keys[] = 'post_status';
        }
        if(!in_array('_birs_appointment_reminded', $this->meta_keys)) {
            $this->meta_keys[] = '_birs_appointment_reminded';
        }
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
        $this['_birs_appointment_padding_before'] = $service->get_padding_before();
        $this['_birs_appointment_padding_after'] = $service->get_padding_after();
        $this['_birs_appointment_duration'] = (int) $this['_birs_appointment_duration'];
        $client = new BIRS_Client($this['_birs_appointment_client'], array(
                    'base_keys' => array(
                        'post_title'
                    )
                ));
        $client->load();
        $this['post_title'] = $service['post_title'] . ' - ' . $client['post_title'];
        $this['_birs_appointment_price'] = floatval($this['_birs_appointment_price']);
        if (!$this->is_id_valid()) {
            $this['_birs_appointment_uid'] = uniqid(rand(), true);
        }
        if(!isset($this['_birs_appointment_reminded'])) {
            $this['_birs_appointment_reminded'] = 0;
        }
        parent::pre_save();
    }

    public function get_appointment_pre_payment_fee() {
        $service = new BIRS_Service($this['_birs_appointment_service'],
            array(
                'meta_keys' => array(
                    '_birs_service_pre_payment_fee'
                )
            )
        );
        $service->load();
        $service_pre_payment_fee = unserialize($service['_birs_service_pre_payment_fee']);
        if($service_pre_payment_fee) {
            if($service_pre_payment_fee['pre_payment_type'] == 'fixed') {
                return floatval($service_pre_payment_fee['fixed']);
            }
            else if($service_pre_payment_fee['pre_payment_type'] == 'percent') {
                return $service_pre_payment_fee['percent'] * 
                    $this['_birs_appointment_price'] * 0.01;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public function get_appointment_payments() {
        $query = new BIRS_Model_Query(
            array(
                'post_type' => 'birs_payment',
                'meta_query' => array(
                    array(
                        'key' => '_birs_payment_appointment',
                        'value' => $this->ID
                    )
                )
            ),
            array(
                'meta_keys' => array(
                    '_birs_payement_appointment', '_birs_payment_client',
                    '_birs_payment_amount', '_birs_payment_type',
                    '_birs_payment_trid', '_birs_payment_notes',
                    '_birs_payment_timestamp', '_birs_payment_currency'
                )
            )
        );
        $payments = $query->query();
        return $payments;
    }

    public function copyFromRequest($request) {
        parent::copyFromRequest($request);
        if(isset($request['birs_appointment_status'])) {
            $this['post_status'] = $request['birs_appointment_status'];
        }
    }
    
}

?>
