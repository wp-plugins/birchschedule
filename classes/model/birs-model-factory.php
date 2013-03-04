<?php

class BIRS_Model_Factory {

    private static $instance;
    
    private function __construct() {
        
    }

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new BIRS_Model_Factory();
        }
        return self::$instance;
    }
    
    public function create_model($post_type, $id, $options) {
        $registry = BIRS_Model_Registry::get_instance();
        $class_name = $registry->get_model_class_name($post_type);
        $clazz = new ReflectionClass($class_name);
        $model = $clazz->newInstance($id, $options);
        return $model;
    }

}

?>
