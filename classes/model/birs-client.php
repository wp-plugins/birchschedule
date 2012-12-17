<?php

class BIRS_Client extends BIRS_Model {

    public function __construct($id, $options) {
        parent::__construct($id, $options);
        $this['post_type'] = 'birs_client';
    }

    public function load_id_by_email() {
        if (!$this['_birs_client_email']) {
            return false;
        }
        $query = new BIRS_Model_Query(
                        array(
                            'post_type' => 'birs_client',
                            'meta_query' => array(
                                array(
                                    'key' => '_birs_client_email',
                                    'value' => $this['_birs_client_email']
                                )
                            )
                        ),
                        array()
        );
        $clients = $query->query();
        if (sizeof($clients) > 0) {
            $client_id = array_shift(array_keys($clients));
            $this['ID'] = $client_id;
            return true;
        }
        return false;
    }

    public function pre_save() {
        $this['post_title'] = $this['_birs_client_name_first'] . ' ' . $this['_birs_client_name_last'];
    }

}

?>
