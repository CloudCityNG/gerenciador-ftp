<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// mnake sure we have the file to edit
if (!isset($_REQUEST['gEditFileId']))
{
    $result          = array();
    $result['error'] = true;
    $result['msg']   = 'File not found.';
    echo json_encode($result);
    exit;
}

// load file
$file = file::loadById((int)$_REQUEST['gEditFileId']);
if (!$file)
{
    $result          = array();
    $result['error'] = true;
    $result['msg']   = 'File not found.';
    echo json_encode($result);
    exit;
}

// load file server
$fileServer = $file->loadServer();

// load all user types
$userTypes = $db->getRows('SELECT id, label FROM user_level ORDER BY id ASC');

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

$result['html'] = '<p style="padding-bottom: 4px;">Use the form below to update the file details.</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="addFileServerForm">';

$result['html'] .= '<div class="form">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Filename:</label>
                        <div class="input">
                            <input name="filename" id="filename" type="text" value="' . validation::safeOutputToScreen($file->getFilenameExcExtension()) . '" class="xxlarge"/>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix">
                        <label>File Owner:</label>
                        <div class="input">
                            <input name="file_owner" id="file_owner" type="text" value="' . validation::safeOutputToScreen($file->getOwnerUsername()) . '" class="large"/>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Short Url:</label>
                        <div class="input">
                            <input name="short_url" id="short_url" type="text" value="' . validation::safeOutputToScreen($file->shortUrl) . '" class="large"/>&nbsp;&nbsp;(the download url, no spacing, alphanumeric only)
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix">
                        <label>Access Password:</label>
                        <div class="input">
                            <input type="checkbox" name="enablePassword" id="enablePassword" value="1" '.(strlen($file->accessPassword)?'CHECKED':'').' onClick="toggleFilePasswordField();">
                            <input name="password" id="password" type="password" class="large" autocomplete="off"'.(strlen($file->accessPassword)?' value="**********"':'').(strlen($file->accessPassword)?'':'READONLY').'/>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Min User Level:</label>
                        <div class="input">
                            <select name="min_user_level" id="min_user_level" class="xlarge">
                                <option value="">- any user type can download this file -</option>';
                                foreach($userTypes AS $userType)
                                {
                                    $result['html'] .= '<option value="'.validation::safeOutputToScreen($userType['id']).'"';
                                    if($userType['id'] == $file->minUserLevel)
                                    {
                                        $result['html'] .= ' SELECTED';
                                    }
                                    $result['html'] .= '>>= '.validation::safeOutputToScreen(UCWords($userType['label'])).'</option>';
                                }
$result['html'] .= '        </select>&nbsp;&nbsp;to download this file. (exc the uploader)
                        </div>
                    </div>';
                    
$result['html'] .= '<div class="clearfix">
                        <label>Mime Type:</label>
                        <div class="input">
                            <input name="mime_type" id="mime_type" type="text" value="' . validation::safeOutputToScreen($file->fileType) . '" class="xxlarge"/>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Real File Path:</label>
                        <span style="width:550px; display: inline-block; padding-top: 6px; padding-left: 20px;">
                            ' . validation::safeOutputToScreen($file->localFilePath) . '&nbsp;&nbsp;(Server: '.validation::safeOutputToScreen($fileServer['serverLabel']).')
                        </span>
                    </div>';

$result['html'] .= '<div class="clearfix">
                        <label>Upload Source:</label>
                        <span style="width:550px; display: inline-block; padding-top: 6px; padding-left: 20px;">
                            ' . validation::safeOutputToScreen(UCWords($file->uploadSource)) .'
                        </span>
                    </div>';

$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Admin File Notes:</label>
                        <div class="input">
                            <textarea name="edit_admin_notes" id="edit_admin_notes" class="xxlarge">'.validation::safeOutputToScreen($file->adminNotes) . '</textarea>
                        </div>
                    </div>';
$result['html'] .= '</form>';

echo json_encode($result);
exit;
