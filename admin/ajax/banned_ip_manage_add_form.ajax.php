<?php

// includes and security
include_once('../_local_auth.inc.php');

$gConfigId   = (int) $_REQUEST['gConfigId'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';
$result['html'] = 'Could not load the form, please try again later.';

$result['html']  = '<p style="padding-bottom: 4px;">Use the form below to add the banned IP address.</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="addBannedIPForm" class="form">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>IP Address:</label>
                        <div class="input">
                            <input name="ip_address" id="ip_address" type="text" value="" class="large"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>Ban Type:</label>
                        <div class="input">
                            <select name="ban_type" id="ban_type" class="large">
                                <option value="Uploading">Uploading</option>
                                <option value="Whole Site">Whole Site</option>
                                <option value="Login">Login</option>
                            </select>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Ban Expiry Date:</label>
                        <div class="input">
                            <input name="ban_expiry_date" id="ban_expiry_date" type="text" value="" class="small"/>&nbsp;&nbsp;(dd/mm/yyyy, leave empty for never)
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>Ban Expiry Time:</label>
                        <div class="input">
                            <select name="ban_expiry_hour" id="ban_expiry_hour" class="xsmall">';
for($i = 0; $i < 24; $i++)
{
    $result['html'] .= '        <option value="'.str_pad($i, 2, '0', STR_PAD_LEFT).'">'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
}
$result['html'] .= '        </select>&nbsp;:&nbsp;<select name="ban_expiry_minute" id="ban_expiry_minute" class="xsmall">';
for($i = 0; $i < 60; $i++)
{
    $result['html'] .= '        <option value="'.str_pad($i, 2, '0', STR_PAD_LEFT).'">'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
}
$result['html'] .= '        </select>&nbsp;&nbsp;(hh:mm)
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Ban Notes:</label>
                        <div class="input">
                            <textarea name="ban_notes" id="ban_notes" class="xxlarge"></textarea>
                        </div>
                    </div>';
$result['html'] .= '</form>';

echo json_encode($result);
exit;
