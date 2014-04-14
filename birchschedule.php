<?php

/*
  Plugin Name: BirchPress Scheduler
  Plugin URI: http://www.birchpress.com
  Description: An appointment booking and online scheduling plugin that allows service businesses to take online bookings.
  Version: 1.7.3
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

require_once 'includes/lessphp/lessc.inc.php';

require_once 'includes/birchschedule/package.php';

$birchschedule->set_plugin_file_path(__FILE__);

$birchschedule->register_class_loader();

$birchpress->util->define_interface();
$birchpress->db->define_interface();
$birchschedule->define_interface();

$birchschedule->load_core();
$birchschedule->load_modules();

$birchschedule->define_modules_lookups_tables();

$birchschedule->define_core_interfaces();
$birchschedule->define_modules_interfaces();

require_once 'includes/legacy_hooks.php';

$birchschedule->product_version = '1.7.3';
$birchschedule->product_name = 'BirchPress Scheduler';
$birchschedule->product_code = 'birchschedule';

$birchschedule->upgrade();

$birchschedule->init_core();
$birchschedule->init_modules();

