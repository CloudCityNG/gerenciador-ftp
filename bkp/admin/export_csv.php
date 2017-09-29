<?php

// allow for 10 minutes for the export
set_time_limit(60 * 10);

// includes and security
include_once('_local_auth.inc.php');

/* resulting csv data */
$formattedCSVData = array();

/* header */
$lArr = array();
$lArr[]             = "Id";
$lArr[]             = "Filename";
$lArr[]             = "Url";
$lArr[]             = "Filesize";
$lArr[]             = "Total Downloads";
$lArr[]             = "Uploaded Date";
$lArr[]             = "Last Accessed";
$formattedCSVData[] = "\"" . implode("\",\"", $lArr) . "\"";

/* get all url data */
$urlData = $db->getRows("SELECT * FROM file ORDER BY uploadedDate asc");
foreach ($urlData AS $row)
{
    $lArr = array();
    $lArr[] = $row['id'];
    $lArr[] = $row['originalFilename'];
    $lArr[] = file::getFileUrl($row['id']);
    $lArr[] = $row['fileSize'];
    $lArr[] = $row['visits'];
    $lArr[] = ($row['uploadedDate'] != "0000-00-00 00:00:00") ? coreFunctions::formatDate($row['uploadedDate']) : "";
    $lArr[] = ($row['lastAccessed'] != "0000-00-00 00:00:00") ? coreFunctions::formatDate($row['lastAccessed']) : "";

    $formattedCSVData[] = "\"" . implode("\",\"", $lArr) . "\"";
}

$outname = "file_data.csv";
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Content-type: text/plain;");
header("Content-Transfer-Encoding: binary;");
header("Content-Disposition: attachment; filename=\"$outname\";");

echo implode("\n", $formattedCSVData);
exit;
