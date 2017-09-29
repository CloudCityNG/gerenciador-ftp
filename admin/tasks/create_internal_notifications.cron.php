<?php

/*
 * Title: Create Internal Noticiations Script
 * Author: YetiShare.com
 * Period: Run once a day
 * 
 * Description:
 * Script to create notifications in user accounts about premium expiry.
 *
 * How To Call:
 * On the command line via PHP, like this:
 * php create_internal_notifications.cron.php
 * 
 * Configure as a cron like this:
 * 0 0 * * * php /path/to/yetishare/install/admin/tasks/create_internal_notifications.cron.php
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

// when to send notifications
$notificationDays = array(1, 2, 4, 7);

// get all accounts reaching paid expiry period
$expiringPaidAccounts = $db->getRows('SELECT id, username, paidExpiryDate, ((UNIX_TIMESTAMP(paidExpiryDate) - UNIX_TIMESTAMP()) / 86400) AS daysUntil FROM users WHERE level_id IN (SELECT id FROM user_level WHERE level_type = \'paid\') AND status = \'active\' AND paidExpiryDate BETWEEN NOW() AND NOW() + INTERVAL '.(int)max($notificationDays).' DAY');
if($expiringPaidAccounts)
{
    foreach($expiringPaidAccounts AS $expiringPaidAccount)
    {
        // days until
        $daysUntil = ceil($expiringPaidAccount['daysUntil']);
        
        // if within one of our notification days, add notification
        if(in_array($daysUntil, $notificationDays))
        {
            $content = t('internal_notification_paid_account_expiring', 'Your paid account is expiring in [[[DAYS]]] days. Your inactive files may removed if you do not renew your membership. Click here for more information.', array('DAYS'=>$daysUntil));
            $link = WEB_ROOT.'/upgrade.html';
            internalNotification::add($expiringPaidAccount['id'], $content, $type = 'entypo-attention', $link);
        }
    }
}

// background task logging
$task->end();