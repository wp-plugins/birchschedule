<?php

final class Birchpress_Db_Imp {

    static function get( $id, $config ) {
        global $birchbase;

        return $birchbase->db->get( $id, $config );
    }

    static function is_valid_id( $id ) {
        global $birchbase;

        return $birchbase->db->is_valid_id( $id );
    }

    static function delete( $id ) {
        global $birchbase;

        return $birchbase->db->delete( $id );
    }

    static function save( $model, $config ) {
        global $birchbase;

        return $birchbase->db->save( $model, $config );
    }

    static function query( $criteria, $config ) {
        global $birchbase;

        return $birchbase->db->query( $criteria, $config );
    }

}
