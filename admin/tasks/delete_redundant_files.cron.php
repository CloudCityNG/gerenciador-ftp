<?php

/*
 * Title: Delete Redundant Files
 * Author: YetiShare.com
 * Period: Every 1 hour
 * 
 * Description:
 * Script to delete any files which are no longer accessed
 *
 * How To Call:
 * On the command line via PHP, like this:
 * php delete_redundant_files.cron.php
 * 
 * Configure as a cron like this:
 * 0 * * * * php /path/to/yetishare/install/admin/tasks/delete_redundant_files.cron.php
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

// delete any old files
file::deleteRedundantFiles();

// background task logging
$task->end();