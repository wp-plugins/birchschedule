<?php

class BIRS_Model_Query {

    private $criteria;
    private $options;
    private static $CLASS_NAME_MAP = array(
        'birs_staff' => 'BIRS_Staff',
        'birs_service' => 'BIRS_Service',
        'birs_client' => 'BIRS_Client',
        'birs_appointment' => 'BIRS_Appointment'
    );

    public function __construct($criteria, $options) {
        $this->criteria = array_merge_recursive(array(
            'nopaging' => true,
            'post_status' => 'publish'
        ), $criteria);
        $this->options = $options;
    }

    public function query() {
        if (!array_key_exists('post_type', $this->criteria)) {
            return array();
        }
        $post_type = $this->criteria['post_type'];
        $class_name = 'BIRS_Model';
        if (isset(self::$CLASS_NAME_MAP[$post_type])) {
            $class_name = self::$CLASS_NAME_MAP[$post_type];
        }
        $posts = get_posts($this->criteria);
        $models = array();
        foreach ($posts as $post) {
            $clazz = new ReflectionClass($class_name);
            $model = $clazz->newInstance($post->ID, $this->options);
            $model->load();
            $models[$post->ID] = $model;
        }
        return $models;
    }

}

?>
