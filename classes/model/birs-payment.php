<?php

class BIRS_Payment extends BIRS_Model {

    public function __construct($id, $options) {
        parent::__construct($id, $options);
        if(!in_array('post_status', $this->base_keys)) {
            $this->base_keys[] = 'post_status';
        }
        $this['post_type'] = 'birs_payment';
	}

	public function pre_save() {
        $this['_birs_payment_amount'] = floatval($this['_birs_payment_amount']);
        parent::pre_save();
	}
}
