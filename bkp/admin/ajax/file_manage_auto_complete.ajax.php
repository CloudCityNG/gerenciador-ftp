<?php

include_once('../_local_auth.inc.php');

$autoComplete = trim($_REQUEST['filterByUser']);

if ((!isset($autoComplete)) || (strlen($autoComplete) < 1)) 
{
	die();
}

$returnQuery = $db->getRows("SELECT username FROM users WHERE username LIKE  '%".$db->escape($autoComplete)."%' ORDER BY username ASC LIMIT 50");

$users = array();
foreach($returnQuery AS $return)
{
	$users[] = $return['username'];
}

echo json_encode($users);