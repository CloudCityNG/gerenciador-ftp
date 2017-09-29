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

<p><?php echo t("file_server_test_ftp_intro", "Testing connection to file server... (via ftp)"); ?></p>
<?php
/* load server details */
$sQL = "SELECT file_server.* ";
$sQL .= "FROM file_server ";
$sQL .= "WHERE file_server.serverType = 'ftp' AND id=" . (int) $serverId;
$row = $db->getRow($sQL);
if (!$row)
{
    echo t("could_not_load_server", "Could not load server details.");
}
else
{
    $serverConfigArr = '';
    if(strlen($row['serverConfig']))
    {
        $serverConfig = json_decode($row['serverConfig'], true);
        if(is_array($serverConfig))
        {
            $serverConfigArr = $serverConfig;
        }
    }
    
    $error = '';

    // start output buffering
    ob_start();
    ob_end_flush();

    echo '<p>- Making sure ftp functions are available in PHP... ';

    // make sure ftp functions exists
    if (!function_exists('ftp_connect'))
    {
        $error = 'Could not find PHP ftp functions! Please contact your host to request they\'re enabled.';
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">FTP functions found.</font></p>';
        echo '<p>- Finding file server ' . $row['serverLabel'] . ' on ip ' . $row['ipAddress'] . ' (port: ' . $row['ftpPort'] . ')... ';

        // connect via ftp
        $conn_id = ftp_connect($row['ipAddress'], $row['ftpPort'], 30);
        if ($conn_id === false)
        {
            $error = 'Could not connect!';
        }
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">Successfully found.</font></p>';
        echo '<p>- Authenticating with stored user \'' . $row['ftpUsername'] . '\' and password [HIDDEN]... ';

        // authenticate
        $login_result = ftp_login($conn_id, $row['ftpUsername'], $row['ftpPassword']);
        if ($login_result === false)
        {
            $error = 'Could not authenticate!';
            // close ftp
            ftp_close($conn_id);
        }
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        if((isset($serverConfigArr['ftp_passive_mode'])) && ($serverConfigArr['ftp_passive_mode'] == 'yes'))
        {
            // enable passive mode
            ftp_pasv($conn_id, true);
        }
        
        echo '<font style="color: green;">Successfully authenticated.</font></p>';
        echo '<p>- Changing to storage directory: ' . $row['storagePath'] . '... ';

        // change directory
        if (ftp_chdir($conn_id, $row['storagePath']) === false)
        {
            $error = 'Could not find storage directory!';
            // close ftp
            ftp_close($conn_id);
        }
    }
    
    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">Successfully changed directory.</font></p>';
        echo '<p>- Attempting test upload to: ' . $row['storagePath'] . '... ';

        $testFile = tmpfile();
        if(!$testFile)
        {
            $error = 'Could not create tmp file for testing upload!';
        }
        else
        {
            // upload test file
            $testFilename = "_yetishare_test_".time().".txt";
            fwrite($testFile, 'YetiShare text file.');
            fseek($testFile, 0);
            if (!ftp_fput($conn_id, $testFilename, $testFile, FTP_BINARY))
            {
                $error = 'Could not upload a file to '.$row['storagePath'].'!';
            }
            else
            {
                // remove test file
                ftp_delete($conn_id, $testFilename);
            }
            fclose($fSetup);
        }
    }

    // output results
    ob_start();
    ob_end_flush();

    if (strlen($error) == 0)
    {
        echo '<font style="color: green;">Successfully uploaded and removed test file.</font></p>';
        // close ftp
        ftp_close($conn_id);
        echo '<p>- Disconnected from ftp.</p>';
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
        echo '<p style="color: green; font-weight:bold;">- No errors found connecting to ' . $row['serverLabel'] . '.</p>';
    }
}
?>

    </body>
</html>