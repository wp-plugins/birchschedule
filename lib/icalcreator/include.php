<?php

function birchschedule_lib_icalcreator_load() {
    if(!class_exists('vcalendar')) {
        require_once dirname(__FILE__) . '/iCalcreator-2.16.12/iCalcreator.class.php';
    }
}