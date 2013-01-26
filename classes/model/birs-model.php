<?php

class BIRS_Model implements ArrayAccess, IteratorAggregate {

    var $data = array();
    var $base_keys = array();
    var $meta_keys = array();
    var $update_base = true;
    var $old_value;

    public function __construct($id, $options=array()) {
        if ((int) $id == $id && (int) $id > 0) {
            $this->data['ID'] = (int) $id;
        }
        if (isset($options['base_keys'])) {
            $this->base_keys = $options['base_keys'];
        } else {
            $this->base_keys = array();
        }
        if (isset($options['meta_keys'])) {
            $this->meta_keys = $options['meta_keys'];
        } else {
            $this->meta_keys = array();
        }
        if(isset($options['update_base'])) {
            $this->update_base = $options['update_base'];
        }
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        } else {
            return null;
        }
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function __set($name, $value) {
        $this[$name] = $value;
    }

    public function __get($name) {
        return $this[$name];
    }
    
    public function getIterator() {
        return new ArrayIterator();
    }

    /**
     * @return boolean; 
     */
    public function load() {
        if (!$this->is_id_valid()) {
            return false;
        }
        $id = $this['ID'];
        if (sizeof($this->base_keys) > 0) {
            $post = get_post($id, ARRAY_A);
            if($post === null) {
                return false;
            }
            foreach ($this->base_keys as $key) {
                $this[$key] = $post[$key];
            }
        }
        if (sizeof($this->meta_keys) > 0) {
            foreach ($this->meta_keys as $key) {
                $this[$key] = get_post_meta($id, $key, true);
            }
        }
        return true;
    }

    public function is_id_valid() {
        return (string) (int) $this['ID'] == $this['ID'] && $this['ID'] > 0;
    }

    public function pre_save() {
        $factory = BIRS_Model_Factory::get_instance();
        $this->old_value = $factory->create_model($this->post_type,
            $this->ID, array(
                'base_keys' => $this->base_keys,
                'meta_keys' => $this->meta_keys
            ));
        if($this->old_value->is_id_valid()) {
            $this->old_value->load();
        } else {
            $this->old_value->post_status = 'new';
        }

    }
    
    public function post_save() {
        if($this->old_value->is_id_valid()) {
            do_action("birchschedule_model_updated_" . $this['post_type'],
                $this, $this->old_value);
        } else {
            do_action("birchschedule_model_inserted_" . $this['post_type'],
                $this);
        }
        if($this->old_value->post_status !== $this->post_status) {
            do_action("birchschedule_model_status_change_" . $this['post_type'],
                $this, $this->old_value);
        }
    }

    public function save() {
        if (!$this['post_type']) {
            return false;
        }
        if (!$this['post_status']) {
            $this['post_status'] = 'publish';
        }
        $this->pre_save();
        $base_data = array(
            'post_type' => $this['post_type'],
            'post_status' => $this['post_status']
        );
        foreach ($this->base_keys as $key) {
            if(isset($this[$key])) {
                $base_data[$key] = $this[$key];
            }
        }
        if (!$this->is_id_valid()) {
            $this['ID'] = wp_insert_post($base_data);
            if (!$this['ID']) {
                return false;
            }
        } 
        else if ($this->update_base) {
            $base_data['ID'] = $this['ID'];
            $this['ID'] = wp_update_post($base_data);
            if (!$this['ID']) {
                return false;
            }
        }
        foreach ($this->meta_keys as $key) {
            if(isset($this[$key])) {
                update_post_meta($this['ID'], $key, $this[$key]);
            }
        }
        $this->post_save();
        return $this['ID'];
    }
    
    public function pre_delete() {
        do_action("birchschedule_model_pre_delete_" . $this['post_type'], $this[ID]);
    }
    
    public function delete() {
        if(!$this->is_id_valid()) {
            return false;
        }
        $this->pre_delete();
        if(wp_delete_post($this->ID)) {
            do_action("birchschedule_model_deleted_" . $this['post_type'], $this->ID);            
        } else {
            return false;
        }
    }

    public function copyFromRequest($request) {
        foreach ($this->base_keys as $key) {
            if (isset($request[$key])) {
                $this[$key] = $request[$key];
            } else {
                $this[$key] = null;
            }
        }
        foreach ($this->meta_keys as $key) {
            $req_key = substr($key, 1);
            if (isset($request[$req_key])) {
                $this[$key] = $request[$req_key];
            } else {
                $this[$key] = null;
            }
        }
    }
    
    public function get_data() {
        return $this->data;
    }
    
}

?>
