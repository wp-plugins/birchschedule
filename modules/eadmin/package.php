<?php

if(!class_exists('Birchschedule_Eadmin')) {

    final class Birchschedule_Eadmin extends Birchpress_Lang_Package {

        public function define_interface() {
        }

    }

    global $birchschedule;

    $birchschedule->eadmin = new Birchschedule_Eadmin();
    $birchschedule->add_module_package($birchschedule->eadmin);
    
}
