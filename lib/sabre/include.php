<?php

function birchschedule_lib_sabre_vobject_load() {
	if(version_compare(phpversion(), '5.3') >= 0) {
	    if(!class_exists('Sabre\VObject\Component\VCalendar')) {
	        require_once dirname(__FILE__) . '/Sabre/VObject/includes.php';
	    }
	    return true;
	} else {
		return false;
	}
}