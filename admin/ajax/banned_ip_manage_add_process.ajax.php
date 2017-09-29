<?php

// includes and security
include_once('../_local_auth.inc.php');

$ip_address        = trim($_REQUEST['ip_address']);
$ban_type          = $_REQUEST['ban_type'];
$ban_notes         = trim($_REQUEST['ban_notes']);
$ban_expiry_date   = trim($_REQUEST['ban_expiry_date']);
$ban_expiry_hour   = trim($_REQUEST['ban_expiry_hour']);
$ban_expiry_minute = trim($_REQUEST['ban_expiry_minute']);

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

if (strlen($ip_address) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("enter_the_ip_address", "Please enter the IP address.");
}
elseif (!validation::validIPAddress($ip_address))
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("ip_address_invalid_try_again", "The format of the IP you've entered is invalid, please try again.");
}
elseif (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}

// check expiry date if set
$ban_expiry = null;
if ($result['error'] == false)
{
    if(strlen($ban_expiry_date))
    {
        $compiledDate = $ban_expiry_date.' '.$ban_expiry_hour.':'.$ban_expiry_minute.':00';
        $d = DateTime::createFromFormat('d/m/Y H:i:s', $compiledDate);
        if((!$d) || ($d->format('d/m/Y H:i:s') != $compiledDate))
        {
            $result['error'] = true;
            $result['msg']   = adminFunctions::t("banned_ip_expiry_date_invalid", "The expiry date is invalid.");
        }
        
        if ($result['error'] == false)
        {
            // check it's not before today
            if($d->format('Y-m-d H:i:s') <= date('Y-m-d H:i:s'))
            {
                $result['error'] = true;
                $result['msg']   = adminFunctions::t("banned_ip_expiry_date_is_in_the_past", "The expiry date is in the past.");
            }
        }
        
        if ($result['error'] == false)
        {
            $ban_expiry = $d->format('Y-m-d H:i:s');
        }
    }
}

if ($result['error'] == false)
{
    $row = $db->getRow('SELECT id FROM banned_ips WHERE ipAddress = ' . $db->quote($ip_address));
    if (is_array($row))
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("ip_address_already_blocked", "The IP address you've entered is already blocked.");
    }
}

if ($result['error'] == false)
{
    

    // add the banned IP
    $dbInsert             = new DBObject("banned_ips", array("ipAddress", "banType", "banNotes", "dateBanned", "banExpiry"));
    $dbInsert->ipAddress  = $ip_address;
    $dbInsert->banType    = $ban_type;
    $dbInsert->banNotes   = $ban_notes;
    $dbInsert->dateBanned = coreFunctions::sqlDateTime();
    $dbInsert->banExpiry  = $ban_expiry;
    if (!$dbInsert->insert())
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("error_problem_record", "There was a problem banning the IP address, please try again.");
    }
    else
    {
        $result['error'] = false;
        $result['msg']   = 'IP address ' . $ip_address . ' has been banned.';
    }
}

echo json_encode($result);
exit;
