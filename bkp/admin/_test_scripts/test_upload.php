<?php

// setup includes, require admin login
require_once('../_local_auth.inc.php');

// handle submission
if(isset($_REQUEST['submitted']))
{
    echo "<strong>Submitted file:</strong><br/><br/>";
    echo "If the file size is zero of the path does not exist below, there is an issue with your server configuration for file uploads. Please contact your host or server admin for further information.<br/>";
	echo "<pre>";
    print_r($_FILES);
	echo "</pre>";
    echo "<br/>";
	
	if(((int)$_FILES['fileToUpload']['size'] > 0) && (strlen($_FILES['fileToUpload']['tmp_name'])))
	{
		echo "<span style='color: #ffffff; padding: 10px; background-color: green; width: 97%; display: block;'>SUCCESS! We found the tmp file and a filesize, it looks like uploads are working fine on your server.</span>";
	}
	else
	{
		echo "<span style='color: #ffffff; padding: 10px; background-color: red; font-weight: bold; width: 97%; display: block;'>ERROR! We could not find the uploaded file. Please contact your server admin to investigate what may be the cause.</span>";
	}
	echo "<br/><br/><br/>";
}
?>
Use the form below to test that file uploads are working on your server:<br/><br/>
<form action="test_upload.php" method="post" enctype="multipart/form-data">
    Select file to upload: (< 2MB)
    <input type="file" name="fileToUpload" id="fileToUpload"/>
    <input type="hidden" value="1" name="submitted"/>
    <input type="submit" value="Upload File" name="submit"/>
</form>