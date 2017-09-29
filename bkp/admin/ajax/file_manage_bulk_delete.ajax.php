<?php
// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = '';

// pick up file ids
$fileIds     = $_REQUEST['fileIds'];
$deleteData = false;
if(isset($_REQUEST['deleteData']))
{
    $deleteData = $_REQUEST['deleteData']=='false'?false:true;
}

if (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
else
{
    $totalRemoved = 0;
    
    // load files
    if(COUNT($fileIds))
    {
        foreach($fileIds AS $fileId)
        {
            // load file and process if active
            $file = file::loadById($fileId);
            if ($file)
            {
                $rs = false;
                if($deleteData == true)
                {
                    // delete
                    $rs = $file->deleteFileIncData();
                }
                elseif($file->statusId == 1)
                {
                    // remove
                    $rs = $file->removeByAdmin();
                }

                if($rs)
                {
                    $totalRemoved++;
                }
            }
        }
    }
}

$result['msg'] = 'Removed '.$totalRemoved.' file'.($totalRemoved!=1?'s':'').'.';

echo json_encode($result);
exit;
