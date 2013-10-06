<?php

/*
  Plugin Name: BirchPress Scheduler
  Plugin URI: http://www.birchpress.com
  Description: An appointment booking and online scheduling plugin that allows service businesses to take online bookings.
  Version: 1.6.5.5
  Author: BirchPress
  Author URI: http://www.birchpress.com
  License: GPLv2
 */

if (!defined('ABSPATH'))
    exit;

@ini_set('memory_limit', -1);

global $birchpress, $birchschedule;

require_once 'includes/birchpress/package.php';
require_once 'includes/birchpress/lang/package.php';
require_once 'includes/birchpress/util/package.php';
require_once 'includes/birchpress/db/package.php';
require_once 'package.php';

$birchpress->util->define_interface();
$birchpress->db->define_interface();
$birchschedule->define_interface();

require_once 'includes/model/package.php';
require_once 'includes/view/package.php';
require_once 'includes/view/bookingform/package.php';
require_once 'includes/view/bookingadmin/package.php';
require_once 'includes/view/calendar/package.php';
require_once 'includes/view/clients/package.php';
require_once 'includes/view/help/package.php';
require_once 'includes/view/locations/package.php';
require_once 'includes/view/payments/package.php';
require_once 'includes/view/services/package.php';
require_once 'includes/view/settings/package.php';
require_once 'includes/view/staff/package.php';
$birchschedule->load_modules();

$birchschedule->model->define_interface();
$birchschedule->view->define_interface();
$birchschedule->view->bookingform->define_interface();
$birchschedule->view->bookingadmin->define_interface();
$birchschedule->view->calendar->define_interface();
$birchschedule->view->clients->define_interface();
$birchschedule->view->help->define_interface();
$birchschedule->view->locations->define_interface();
$birchschedule->view->payments->define_interface();
$birchschedule->view->services->define_interface();
$birchschedule->view->settings->define_interface();
$birchschedule->view->staff->define_interface();
$birchschedule->define_modules_interfaces();

$birchschedule->product_version = '1.6.5.5';
$birchschedule->product_name = 'BirchPress Scheduler';
$birchschedule->product_code = 'birchschedule';

$birchschedule->upgrade();

$birchschedule->view->init();
$birchschedule->view->bookingform->init();
$birchschedule->view->bookingadmin->init();
$birchschedule->view->calendar->init();
$birchschedule->view->clients->init();
$birchschedule->view->help->init();
$birchschedule->view->locations->init();
$birchschedule->view->payments->init();
$birchschedule->view->services->init();
$birchschedule->view->settings->init();
$birchschedule->view->staff->init();
$birchschedule->init_modules();

