<?php
/*
 * File API for cross site deletes, uploads, etc.
 * 
 * Requires a valid api key and username. Call in the format:
 * http://yoursite.com/api/?key=[key]&username=[username]&action=[method]&file_id=123
 * 
 * Output is json encoded result.
 * 
 * Available methods. Note each may require additional parameters to be passed:
 * 
 * list - Gets list of file ids and urls within account. No additional parameters required
 * info - Gets details about a file within the logged in account. Requires file_id
 * delete - Deletes a file. Requires file_id
 * movefile - Moves a file from 1 server to another.
 */

// setup includes
require_once('../core/includes/master.inc.php');

// required variables
$key = '';
if($_REQUEST['key'])
{
    $key = $_REQUEST['key'];
}

if(strlen($key) == 0)
{
    api::outputError('Error: API access key not found.');
}

$username = '';
if($_REQUEST['username'])
{
    $username = $_REQUEST['username'];
}

if(strlen($username) == 0)
{
    api::outputError('Error: Username not found.');
}

$action = '';
if($_REQUEST['action'])
{
    $action = $_REQUEST['action'];
}

if(strlen($action) == 0)
{
    api::outputError('Error: Action not found.');
}

// start api class
$api = new api($key, $username);

// make sure user has access
$rs = $api->validateAccess();
if(!$rs)
{
    api::outputError('Error: Could not validate api access details.');
}

// check action exists
$actualMethod = 'api'.UCFirst($action);
if(!method_exists($api, $actualMethod))
{
    api::outputError('Error: Action of \''.$action.'\' not found. Method: '.$actualMethod.'()');
}

// call action
$rs = call_user_func(array($api, $actualMethod), $_REQUEST);
echo $rs;
