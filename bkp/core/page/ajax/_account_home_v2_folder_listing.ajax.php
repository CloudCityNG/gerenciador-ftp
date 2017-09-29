<?php

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

// some initial headers
header("HTTP/1.0 200 OK");
header('Content-type: application/json; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

// prepare clause for user owned folders
$clause = '(userId = '.(int)$Auth->id.' AND ';
if((isset($_REQUEST['folder'])) && ($_REQUEST['folder'] != -1))
{
    $folder = $_REQUEST['folder'];
    $clause .= 'parentId = '.(int)$folder;
	
	// clause to add any shared folders
	// SHARE CODE - DISABLED UNTIL THE NEXT RELEASE
	//$clause .= ') OR (file_folder_share.shared_with_user_id = '.(int)$Auth->id.' AND (file_folder.parentId NOT IN (SELECT file_folder_share.folder_id FROM file_folder_share WHERE shared_with_user_id = '.(int)$Auth->id.') OR file_folder.parentId = '.(int)$folder.')';
}
else
{
    $clause .= 'parentId IS NULL';
	
	// clause to add any shared folders
	// SHARE CODE - DISABLED UNTIL THE NEXT RELEASE
	//$clause .= ') OR (file_folder_share.shared_with_user_id = '.(int)$Auth->id.' AND (file_folder.parentId NOT IN (SELECT folder_id FROM file_folder_share WHERE shared_with_user_id = '.(int)$Auth->id.') OR file_folder.parentId IS NULL)';
}
$clause .= ')';



$rs = array();

// load folder data for user
// SHARE CODE - DISABLED UNTIL THE NEXT RELEASE
//$rows = $db->getRows('SELECT file_folder.id, folderName, (SELECT COUNT(ffchild.id) AS total FROM file_folder ffchild WHERE ffchild.parentId = file_folder.id) AS childrenCount, accessPassword, (SELECT COUNT(file.id) AS total FROM file WHERE folderId = file_folder.id AND file.statusId = 1) AS fileCount, file_folder_share.shared_with_user_id, file_folder_share.share_permission_level FROM file_folder LEFT JOIN file_folder_share ON file_folder.id = file_folder_share.folder_id WHERE '.$clause.' ORDER BY folderName');
$rows = $db->getRows('SELECT file_folder.id, folderName, (SELECT COUNT(ffchild.id) AS total FROM file_folder ffchild WHERE ffchild.parentId = file_folder.id) AS childrenCount, accessPassword, (SELECT COUNT(file.id) AS total FROM file WHERE folderId = file_folder.id AND file.statusId = 1) AS fileCount FROM file_folder WHERE '.$clause.' ORDER BY folderName');
if($rows)
{
    foreach($rows AS $row)
    {
        $folderType = 'folder';
        if(((int)$row['fileCount'] > 0) || ((int)$row['childrenCount'] > 0))
        {
            $folderType = 'folderfull';
        }
        
        if(strlen($row['accessPassword']))
        {
            $folderType = 'folderpassword';
        }
		
		if($row['shared_with_user_id'] == $Auth->id)
        {
            $folderType = 'foldershared';
        }

        if((int)$row['childrenCount'] > 0)
        {
            $rs[] = array('data'=>$row['folderName'].(((int)$row['fileCount']>0)?(' ('.number_format($row['fileCount']).')'):'').' ', 'attr'=>array('id'=>$row['id'], 'title'=>t('account_home_folder_treeview_double_click', 'Double click to view/hide subfolders'), 'rel'=>$folderType), 'children'=> array('state'=>'closed'), 'state'=>'closed');
        }
        else
        {
            $rs[] = array('data'=>$row['folderName'].(((int)$row['fileCount']>0)?(' ('.number_format($row['fileCount']).')'):''), 'attr'=>array('id'=>$row['id'], 'title'=>'', 'rel'=>$folderType));
        }
    }
}

echo json_encode($rs);