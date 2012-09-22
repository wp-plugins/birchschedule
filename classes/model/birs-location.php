<?php

class BIRS_Location extends BIRS_Model {
    
    public function __construct($id, $options) {
        parent::__construct($id, $options);
        $this['post_type'] = 'birs_location';
    }
}

?>
