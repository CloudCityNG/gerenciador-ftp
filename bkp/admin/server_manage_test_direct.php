<?php
// includes and security
include_once('_local_auth.inc.php');

if (!isset($_REQUEST['serverId']))
{
    die('Could not find server id.');
}
else
{
    $serverId = (int) $_REQUEST['serverId'];
}
?>

<html lang="en-us">

    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=1" >
        <meta charset="utf-8" />

        <link rel="apple-touch-con" href="" />

        <title><?php echo htmlentities(UCwords(ADMIN_PAGE_TITLE)); ?> - Admin</title>

        <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">

        <!-- The Columnal Grid and mobile stylesheet -->
        <link rel="stylesheet" href="assets/styles/columnal/columnal.css" type="text/css" media="screen" />

        <!-- Fixes for IE -->

        <!--[if lt IE 9]>
            <link rel="stylesheet" href="assets/styles/columnal/ie.css" type="text/css" media="screen" />
            <link rel="stylesheet" href="assets/styles/ie8.css" type="text/css" media="screen" />
            <script src="assets/scripts/IE9.js"></script>
        <![endif]-->        


        <!-- Use CDN on production server -->
        <script src="assets/scripts/jquery.min.js"></script>
        <script src="assets/scripts/jquery-ui.min.js"></script>

        <!-- Now that all the grids are loaded, we can move on to the actual styles. --> 
        <link rel="stylesheet" href="assets/scripts/jqueryui/jqueryui.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="assets/styles/style.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="assets/styles/global.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="assets/styles/config.css" type="text/css" media="screen" />

        <!-- Adds HTML5 Placeholder attributes to those lesser browsers (i.e. IE) -->
        <script type="text/javascript" src="assets/scripts/jquery.placeholder.1.2.min.shrink.js"></script>

        <!-- Sortable, searchable DataTable -->
        <script src="assets/scripts/jquery.dataTables.min.js"></script>

        <!-- Adds HTML5 Placeholder attributes to those lesser browsers (i.e. IE) -->
        <script type="text/javascript" src="assets/scripts/jquery.placeholder.1.2.min.shrink.js"></script>

        <!-- Adds charts -->
        <script type="text/javascript" src="assets/scripts/flot/jquery.flot.min.js"></script>
        <script type="text/javascript" src="assets/scripts/flot/jquery.flot.pie.min.js"></script>
        <script type="text/javascript" src="assets/scripts/flot/jquery.flot.stack.min.js"></script>

        <!-- Form Validation Engine -->
        <script src="assets/scripts/formvalidator/jquery.validationEngine.js"></script>
        <script src="assets/scripts/formvalidator/jquery.validationEngine-en.js"></script>
        <link rel="stylesheet" href="assets/scripts/formvalidator/validationEngine.jquery.css" type="text/css" media="screen" />

        <!-- Custom Tooltips -->
        <script src="assets/scripts/twipsy.js"></script>

        <!-- WYSIWYG Editor -->
        <script src="assets/scripts/cleditor/jquery.cleditor.min.js"></script>
        <link rel="stylesheet" href="assets/scripts/cleditor/jquery.cleditor.css" type="text/css" media="screen" />

        <!-- Fullsized calendars -->
        <link rel="stylesheet" href="assets/scripts/fullcalendar/fullcalendar.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="assets/scripts/fullcalendar/fullcalendar.print.css" type="text/css" media="print" />
        <script src="assets/scripts/fullcalendar/fullcalendar.min.js"></script>
        <script src="assets/scripts/fullcalendar/gcal.js"></script>

        <!-- Colorbox is a lightbox alternative-->
        <script src="assets/scripts/colorbox/jquery.colorbox-min.js"></script>
        <link rel="stylesheet" href="assets/scripts/colorbox/colorbox.css" type="text/css" media="screen" />

        <!-- Colorpicker -->
        <script src="assets/scripts/colorpicker/colorpicker.js"></script>
        <link rel="stylesheet" href="assets/scripts/colorpicker/colorpicker.css" type="text/css" media="screen" />

        <!-- Uploadify -->
        <script type="text/javascript" src="assets/scripts/uploadify/jquery.uploadify.v2.1.4.min.js"></script>
        <script type="text/javascript" src="assets/scripts/uploadify/swfobject.js"></script>
        <link rel="stylesheet" href="assets/scripts/uploadify/uploadify.css" type="text/css" media="screen" />

        <!-- Menu -->
        <link rel="stylesheet" href="assets/scripts/superfish/superfish.css" type="text/css" media="screen" />
        <script src="assets/scripts/superfish/superfish.js"></script>

        <!-- ddslick, for images in dropdown menus -->
        <script src="assets/scripts/jquery.ddslick.min.js"></script>

        <!-- Js used in the theme -->
        <script src="assets/scripts/global.js"></script>

    </head>
    <body style="background: #ffffff;">

<p><?php echo t("file_server_test_direct_intro", "Testing file server... (direct file server)"); ?></p>
<?php
/* load server details */
$sQL = "SELECT file_server.* ";
$sQL .= "FROM file_server ";
$sQL .= "WHERE file_server.serverType = 'direct' AND id=" . (int) $serverId;
$row = $db->getRow($sQL);
if (!$row)
{
    echo t("could_not_load_server", "Could not load server details.");
}
else
{
    $error = '';

    // start output buffering
    ob_start();
    ob_end_flush();

    echo '<p>- Testing that server and path is available '._CONFIG_SITE_PROTOCOL.'://' . $row['fileServerDomainName'] .$row['scriptPath']. '... ';

    // check site headers
    $headers = get_headers(_CONFIG_SITE_PROTOCOL.'://'.$row['fileServerDomainName'].$row['scriptPath'].'_config.inc.php');
    $responseCode = substr($headers[0], 9, 3);
    if ($responseCode != 200)
    {
        $error = 'Could not see the file server or the required php files. Response code: '.$responseCode;
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">Successfully found server.</font></p>';
        echo '<p>- Checking connectivity to the site database from the file server... ';

        // check database connectivity
        $rs = coreFunctions::getRemoteUrlContent($row['fileServerDomainName'].$row['scriptPath']);
        if (strpos(strtolower($rs), 'failed connecting to the database'))
        {
            $error = 'Problem connecting to the main script database from your file server. Ensure the settings in /_config.inc.php are correct and that your MySQL user has privileges to connect remotely.!';
        }
    }
    
    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">Database ok.</font></p>';
        echo '<p>- Testing mod rewrite and .htaccess file... ';

        // check site headers
        $headers = get_headers(_CONFIG_SITE_PROTOCOL.'://'.$row['fileServerDomainName'].$row['scriptPath'].'/contact.html');
        $responseCode = substr($headers[0], 9, 3);
        if ($responseCode != 200)
        {
            $error = 'Could not validate that the .htaccess file had been created on the file server or that mod rewrite was enabled, please check and try again.';
        }
    }
    
    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">Mod Rewrite &amp; .htaccess ok.</font></p>';
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<p>- Setting up server paths in database... ';

        // attempt to get server details, requires login
        $url = crossSiteAction::appendUrl(_CONFIG_SITE_PROTOCOL.'://'.$row['fileServerDomainName'].$row['scriptPath'].'/'.ADMIN_FOLDER_NAME.'/ajax/server_manage_get_server_detail.ajax.php');
        $responseJson = coreFunctions::getRemoteUrlContent($url);
        if (strlen($responseJson) == 0)
        {
            $error = 'Could not get access to the server paths, no response. Url: '.$url;
        }
        
        // attempt to convert to array
        if (strlen($error) == 0)
        {
            $responseArr = json_decode($responseJson, true);
            if(!is_array($responseArr))
            {
                $error = 'Could not convert response into array to read the data. ('.$responseJson.') Url: '.$url;
            }
            else
            {
                // update database
                fileServer::setDocRootData($serverId, $responseArr['server_doc_root']);
            }
        }
    }
    
    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">Found server information and updated local data.</font></p>';
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) > 0)
    {
        echo '<font style="color: red; font-weight:bold;">' . $error . '</font></p>';
    }
    else
    {
        echo '<p style="color: green; font-weight:bold;">- No errors found using file server ' . $row['fileServerDomainName'] . '.</p>';
    }
}
?>

    </body>
</html>
