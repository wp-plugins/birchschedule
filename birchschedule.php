<?php

/*
  Plugin Name: BirchPress Scheduler
  Plugin URI: http://www.birchpress.com
  Description: An appointment booking and online scheduling plugin that allows service businesses to take online bookings.
  Version: 1.9
  Author: BirchPress
  Author URI: http://www.birchpress.com
  License: GPLv2
 */

if (!defined('ABSPATH'))
    exit;

require_once 'framework/includes/birchpress.inc.php';

require_once 'includes/package.php';
require_once 'includes/legacy_hooks.php';

global $birchschedule, $birchpress;

$birchschedule->set_plugin_file_path(__FILE__);
$birchpress->set_plugin_url($birchschedule->plugin_url());

$birchschedule->product_version = '1.9';
$birchschedule->product_name = 'BirchPress Scheduler';
$birchschedule->product_code = 'birchschedule';

$birchschedule->run();

