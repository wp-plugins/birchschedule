<?php

class BIRS_Model_Registry {
    private static $instance;
    
    private $class_name_map = array();

    private function __construct() {
        
    }

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new BIRS_Model_Registry();
        }
        return self::$instance;
    }

    public function register_model($post_type, $class_name) {
        $this->class_name_map[$post_type] = $class_name;
    }

    public function get_model_class_name($post_type) {
        $class_name = 'BIRS_Model';
        if (isset($this->class_name_map[$post_type])) {
            $class_name = $this->class_name_map[$post_type];
        }
        return $class_name;
    }

}

?>
