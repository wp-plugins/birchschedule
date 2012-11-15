<?php

class BIRS_Model implements ArrayAccess {

    private $data = array();
    private $base_keys = array();
    private $meta_keys = array();

    public function __construct($id, $options) {
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
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
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

    /**
     * @return boolean; 
     */
    public function load() {
        if (!$this->is_id_valid()) {
            return false;
        }
        if (sizeof($this->base_keys) > 0) {
            $post = get_post($this['ID'], ARRAY_A);
            foreach ($this->base_keys as $key) {
                $this[$key] = $post[$key];
            }
        }
        if (sizeof($this->meta_keys) > 0) {
            foreach ($this->meta_keys as $key) {
                $this[$key] = get_post_meta($this['ID'], $key, true);
            }
        }
        return true;
    }

    private function is_id_valid() {
        return (string) (int) $this['ID'] == $this['ID'] && $this['ID'] > 0;
    }

    public function pre_save() {
        
    }

    public function save() {
        if (!$this['post_type']) {
            return false;
        }
        $this->pre_save();
        $base_data = array(
            'post_type' => $this['post_type'],
            'post_status' => 'publish'
        );
        foreach ($this->base_keys as $key) {
            $base_data[$key] = $this[$key];
        }
        if (!$this->is_id_valid()) {
            $this['ID'] = wp_insert_post($base_data);
            if (!$this['ID']) {
                return $this['ID'];
            }
        } else {
            $base_data['ID'] = $this['ID'];
            wp_update_post($base_data);
        }
        foreach ($this->meta_keys as $key) {
            update_post_meta($this['ID'], $key, $this[$key]);
        }
        return $this['ID'];
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

}

?>
