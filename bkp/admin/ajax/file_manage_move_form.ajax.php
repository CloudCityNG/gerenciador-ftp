<?php

// includes and security
include_once('../_local_auth.inc.php');

// load file servers
$fileServers = $db->getRows('SELECT file_server.id, file_server.serverLabel FROM file_server WHERE statusId=2 AND serverType IN (\'local\', \'direct\') ORDER BY serverLabel ASC');
if (COUNT($fileServers) == 0)
{
    $result          = array();
    $result['error'] = false;
    $result['msg']   = '';
    $result['html']  = 'No active servers.';
    echo json_encode($result);
    exit;
}

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

$result['html'] = '<p>Choose which server to move the selected files to. Note that this process adds the item to the file_action queue, so the actual move will happen over the next few minutes. Only \'local\' and \'direct\' servers are currently supported.</p>';
$result['html'] .= '<p style="padding-bottom: 4px;">If you select the current server the file is stored on, it\'ll just be ignored.</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="addFileServerForm">';

$result['html'] .= '<div class="form">';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("file_servers", "File Servers")) . ':</label>
                        <div class="input">
                            <select name="server_ids" id="server_ids" class="xxlarge">';
foreach ($fileServers AS $fileServer)
{
    $result['html'] .= '        <option value="' . $fileServer['id'] . '"';
    $result['html'] .= '        >' . UCWords($fileServer['serverLabel']) . '</option>';
}
$result['html'] .= '        </select>
                        </div>
                    </div>';
$result['html'] .= '</div>';

$result['html'] .= '</form>';

echo json_encode($result);
exit;
