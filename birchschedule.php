<?php

/*
  Plugin Name: BirchPress Scheduler
  Plugin URI: http://www.birchpress.com
  Description: An appointment booking and online scheduling plugin that allows service businesses to take online bookings.
  Version: 1.8.1
  Author: BirchPress
  Author URI: http://www.birchpress.com
  License: GPLv2
 */

if (!defined('ABSPATH'))
    exit;

require_once 'birchpress/includes/birchpress.inc.php';

require_once 'includes/birchschedule/package.php';
require_once 'includes/legacy_hooks.php';

global $birchschedule;

$birchschedule->product_version = '1.8.1';
$birchschedule->product_name = 'BirchPress Scheduler';
$birchschedule->product_code = 'birchschedule';

$birchschedule->set_plugin_file_path(__FILE__);

$birchschedule->run();

