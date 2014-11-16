<?php

if(!class_exists('Birchschedule_Gsettings')) {

    final class Birchschedule_Gsettings extends Birchpress_Lang_Package {

        public function define_interface() {
            global $birchschedule;

            $this->define_function('upgrade_module',
                array('Birchschedule_Gsettings_Upgrader', 'upgrade_module'));

            $birchschedule->define_method('upgrade_module', 'gsettings',
                array($this, 'upgrade_module'));

        }

    }

    global $birchschedule;

    $birchschedule->gsettings = new Birchschedule_Gsettings();

    $birchschedule->add_module_package($birchschedule->gsettings);
    
}
