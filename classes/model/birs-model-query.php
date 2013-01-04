<?php

class BIRS_Model_Query {

    private $criteria;
    private $options;

    public function __construct($criteria, $options = array()) {
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
        $registry = BIRS_Model_Registry::get_instance();
        $class_name = $registry->get_model_class_name($post_type);
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
