<?php
// increase time limit to 30 minutes
set_time_limit(30*60);

// includes and security
include_once('_local_auth.inc.php');

if (!isset($_REQUEST['languageId']))
{
    die('Could not find language id.');
}
else
{
    $languageId = (int) $_REQUEST['languageId'];
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

<p>Getting English content in preparation for automatic translation...</p>
<?php
$languageItem = $db->getRow("SELECT languageName, language_code FROM language WHERE id = ".(int)$languageId." LIMIT 1");
$languageData = $db->getRows("SELECT language_content.id, language_content.is_locked, language_content.content, language_key.languageKey, language_key.id AS languageKeyId, language_key.defaultContent FROM language_content LEFT JOIN language_key ON language_content.languageKeyId = language_key.id LEFT JOIN language ON language_content.languageId = language.id WHERE language.id = ".(int)$languageId." AND language_content.is_locked = 0 ORDER BY languageKey");
if (!$languageData)
{
    echo t("could_not_load_the_language_content", "Could not load language content.");
}
else
{
    // start output buffering
    coreFunctions::flushOutput();
	
	// 1KB of initial data, required by Webkit browsers
	echo "<span style='display: none;'>" . str_repeat("0", 1024) . "</span>";

    echo '<p>- Found '.COUNT($languageData).' items (which aren\'t locked). Translating to \''.$languageItem['language_code'].'\' ('.$languageItem['languageName'].')...</p>';

    // output results
    coreFunctions::flushOutput();

	// do the translation, ensuring no more than 100 per second
	$googleTranslate = new googleTranslate($languageItem['language_code']);
	$tracker = 1;
	foreach($languageData AS $languageDataItem)
	{
		$translation = $googleTranslate->translate($languageDataItem['defaultContent']);
		if ($translation !== false)
		{
			// update item within the database, also set as locked so this process can be run from where it finished if it fails
			$db->query('UPDATE language_content SET content='.$db->quote($translation).', is_locked = 1 WHERE id = '.$languageDataItem['id'].' AND is_locked = 0 LIMIT 1');
			
			// onscreen progress
			if($tracker % 50 == 0)
			{
				// output results
				echo '<p>- Completed '.$tracker.' translations...</p>';
				coreFunctions::flushOutput();
			}
			$tracker++;
		}
		else
		{
			die('<font style="color: red;">'.$googleTranslate->getError().'</font>');
		}
	}

    // output results
    coreFunctions::flushOutput();

    echo '<p style="color: green; font-weight:bold;">- Auto translation of '.COUNT($languageData).' items to \''.$languageItem['language_code'].'\' ('.$languageItem['languageName'].') complete.</p>';
}
?>

    </body>
</html>
