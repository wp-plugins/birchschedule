<?php

class Birchpress_Db_Imp {
    
    private function __construct() {

    }

    static function get($id, $config) {
        birch_assert(is_array($config));
        birch_assert(isset($config['base_keys']) && 
            is_array($config['base_keys']));
        birch_assert(isset($config['meta_keys']) && 
            is_array($config['meta_keys']));

        global $birchpress;

        if(!$birchpress->db->is_valid_id($id)) {
            return false;
        }
        $config['base_keys'] = array_merge(array('post_type'), $config['base_keys']);
        $model = array(
                'ID' => $id
            );
        $post = get_post($id, ARRAY_A);
        if($post === null) {
            return false;
        }
        foreach ($config['base_keys'] as $key) {
            $model[$key] = $post[$key];
        }
        if (sizeof($config['meta_keys']) > 0) {
            foreach ($config['meta_keys'] as $key) {
                $model[$key] = get_post_meta($id, $key, true);
            }
        }
        return $model;
    }

    static function is_valid_id($id) {
        return (string) (int) $id == $id && $id > 0;
    }

    static function delete($id) {
        birch_assert((string) (int) $id == $id && $id > 0);
        return wp_delete_post($id); 
    }

    static function save($model, $config) {
        birch_assert(is_array($model));
        birch_assert(isset($model['post_type']));
        birch_assert(is_array($config));
        birch_assert(isset($config['base_keys']) && 
            is_array($config['base_keys']));
        birch_assert(isset($config['meta_keys']) && 
            is_array($config['meta_keys']));

        global $birchpress;

        $id = 0;
        if(isset($model['ID'])) {
            if($birchpress->db->is_valid_id($model['ID'])) {
                $id = $model['ID'];
            } else {
                unset($model['ID']);
            }
        }
        $model_fields = array_keys($model);
        foreach($model_fields as $field) {
            if(!in_array($field, $config['base_keys']) && 
                !in_array($field, $config['meta_keys']) &&
                $field != 'ID' && $field != 'post_type') {
                unset($model[$field]);
            }
        }
        if($config['base_keys'] || !$id) {
            if(!isset($model['post_status'])) {
                $model['post_status'] = 'publish';
            }
            $id = wp_insert_post($model);
        }
        if(!$id) {
            return false;
        }
        foreach($config['meta_keys'] as $key) {
            if(isset($model[$key])) {
                update_post_meta($id, $key, $model[$key]);
            }
        }
        return $id;
    }

    static function query($criteria, $config) {
        $criteria = array_merge(
            array(
                'nopaging' => true,
                'post_status' => 'publish'
            ), 
            $criteria
        );
        $query = new WP_Query();
        $posts = $query->query($criteria);
        $models = array();
        foreach($posts as $post) {
            $model = self::get($post->ID, $config);
            $models[$post->ID] = $model;
        }
        return $models;
    }

}
