<?php

/*
 * Title: Process File Action
 * Author: YetiShare.com
 * Period: Run every 5 minutes
 * 
 * Description:
 * Script to process any pending actions in the file_action table queue
 *
 * How To Call:
 * On the command line via PHP, like this:
 * php process_file_queue.cron.php
 * 
 * Configure as a cron like this:
 * *\/5 * * * * php /path/to/yetishare/install/admin/tasks/process_file_queue.cron.php
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
                            
// process delete queue
fileAction::processQueue('delete');

// process move queue
fileAction::processQueue('move', 1);

// process restoration queue
fileAction::processQueue('restore', 50);

// background task logging
$task->end();