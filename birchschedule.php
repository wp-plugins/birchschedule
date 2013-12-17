<?php

/*
  Plugin Name: BirchPress Scheduler
  Plugin URI: http://www.birchpress.com
  Description: An appointment booking and online scheduling plugin that allows service businesses to take online bookings.
  Version: 1.6.11
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

require_once 'includes/birchschedule/package.php';

$birchschedule->set_plugin_file_path(__FILE__);

$birchpress->util->define_interface();
$birchpress->db->define_interface();
$birchschedule->define_interface();

require_once 'includes/birchschedule/model/package.php';
require_once 'includes/birchschedule/view/package.php';
require_once 'includes/birchschedule/view/bookingform/package.php';
require_once 'includes/birchschedule/view/bookingadmin/package.php';
require_once 'includes/birchschedule/view/calendar/package.php';
require_once 'includes/birchschedule/view/clients/package.php';
require_once 'includes/birchschedule/view/help/package.php';
require_once 'includes/birchschedule/view/locations/package.php';
require_once 'includes/birchschedule/view/payments/package.php';
require_once 'includes/birchschedule/view/services/package.php';
require_once 'includes/birchschedule/view/settings/package.php';
require_once 'includes/birchschedule/view/staff/package.php';
$birchschedule->load_modules();

$birchschedule->define_modules_lookups_tables();

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

require_once 'includes/legacy_hooks.php';

$birchschedule->product_version = '1.6.11';
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

