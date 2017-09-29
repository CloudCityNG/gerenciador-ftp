<?php

/*
 * Title: Downgrade Expired Accounts
 * Author: YetiShare.com
 * Period: Every 15 monutes
 * 
 * Description:
 * Script to downgrade any accounts which are no longer premium
 *
 * How To Call:
 * On the command line via PHP, like this:
 * php downgrade_accounts.php
 * 
 * Configure as a cron like this:
 * 0 0 * * * php /path/to/yetishare/install/admin/tasks/downgrade_accounts.cron.php
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

// downgrade accounts
UserPeer::downgradeExpiredAccounts();

// background task logging
$task->end();