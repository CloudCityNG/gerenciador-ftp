<?php
// available params
// $params['fileObj']
// $params['extraMenuItems']

// only for active files
if($params['fileObj']->statusId == 1)
{
	$params['extraMenuItems']['Select'] = array("label"=>UCWords(t('account_file_details_select_file', 'Select File')), "separator_after"=>true, "action"=>"function() { selectFile(fileId, true);  }");
}
