<?php

/*
 * Title: Process Plugin Tasks
 * Author: YetiShare.com
 * Period: Run every hour
 * 
 * Description:
 * Script to process any tasks within plugins
 *
 * How To Call:
 * On the command line via PHP, like this:
 * php plugin_tasks.cron.php
 * 
 * Configure as a cron like this:
 * 0 * * * * php /path/to/yetishare/install/admin/tasks/plugin_tasks.cron.php
 */

// setup environment
define('CLI_MODE', true);
define('ADMIN_IGNORE_LOGIN', true);
define('LOCAL_ADMIN_PATH', dirname(dirname(__FILE__)));

// includes and security
include_once(LOCAL_ADMIN_PATH.'/_local_auth.inc.php');

// background task logging
$task = new backgroundTask();
$task->start();

// do any batch tasks in the plugins
pluginHelper::includeAppends('batch_tasks.php');

// background task logging
$task->end();