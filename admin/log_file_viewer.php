<?php
define('ADMIN_PAGE_TITLE', 'View Log Files');
define('ADMIN_SELECTED_PAGE', 'configuration');
include_once('_local_auth.inc.php');
include_once('_header.inc.php');

define('LOG_FILE_LIMIT_OUTPUT_LINES', 1000);
?>
<script>
$(document).ready(function(){
    var psconsole = $('#logViewer');
	if(typeof(psconsole[0]) != "undefined")
	{
		psconsole.scrollTop(
			psconsole[0].scrollHeight - psconsole.height()
		);
	}
});
</script>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon largeLogIcon"></div>

        <div class="widget clearfix">
            <h2>View Log Files<?php
            if(isset($_REQUEST['lType']))
            {
                echo ' (';
                echo adminFunctions::makeSafe($_REQUEST['lType']);
                if(isset($_REQUEST['lFile']))
                {
                    echo '/'.adminFunctions::makeSafe($_REQUEST['lFile']);
                }
                echo ')';
            }
            ?></h2>
            <div class="widget_inside">
                <?php
                if (_CONFIG_DEMO_MODE == true)
                {
                    adminFunctions::setError("Viewing the log files is not permitted in demo mode.");
                    echo adminFunctions::compileErrorHtml();
                }
                else
                {
                    // get list of log file types
                    $logFileTypes = array();
                    if ($handle       = opendir(LOCAL_SITE_CONFIG_BASE_LOG_PATH))
                    {
                        // loop contents
                        while (false !== ($entry = readdir($handle)))
                        {
                            if ((substr($entry, 0, 1) != '.') && (is_dir(LOCAL_SITE_CONFIG_BASE_LOG_PATH . $entry)))
                            {
                                $logFileTypes[] = $entry;
                            }
                        }
                        closedir($handle);
                        asort($logFileTypes);
                    }

                    if (COUNT($logFileTypes) == 0)
                    {
                        echo 'Could not find any log files in the log folder - ' . LOCAL_SITE_CONFIG_BASE_LOG_PATH;
                    }
                    else
                    {
                        // show log file contents
                        if((isset($_REQUEST['lFile'])) && (isset($_REQUEST['lType'])))
                        {
                            // pickup the params
                            $lFile = $_REQUEST['lFile'];
                            $lType = $_REQUEST['lType'];
                            
                            // make safe
                            $lFile = str_replace(array('..', './', '../'), '', $lFile);
                            $lType = str_replace(array('..', './', '../'), '', $lType);
                            
                            // log file path
                            $logPath = LOCAL_SITE_CONFIG_BASE_LOG_PATH.$lType.'/'.$lFile;
                            
                            // double check the file exists
                            if(file_exists($logPath))
                            {
                                // get file contents, limit by top LOG_FILE_LIMIT_OUTPUT_LINES lines
                                $logLines = log::readLogFile($logPath, LOG_FILE_LIMIT_OUTPUT_LINES);
                                echo 'Log file contents below, only the most recent '.LOG_FILE_LIMIT_OUTPUT_LINES.' lines are shown.<br/><br/><br/>';
                                echo '<textarea id="logViewer" class="logViewer">';
                                if(COUNT($logLines))
                                {
                                    foreach($logLines AS $logLine)
                                    {
                                        echo adminFunctions::makeSafe($logLine);
                                    }
                                }
                                echo '</textarea>';
                                echo '<br/>';
                                echo '<br/>';
                                echo '<br/>';
                                echo '<a href="log_file_viewer.php?lType=' . adminFunctions::makeSafe($lType) . '">< back</a>';
                            }
                            else
                            {
                                adminFunctions::setError('Error: Could not find log file - '.$logPath);
                                echo adminFunctions::compileErrorHtml();
                                echo '<br/>';
                                echo '<br/>';
                                echo '<br/>';
                                echo '<a href="log_file_viewer.php?lType=' . adminFunctions::makeSafe($lType) . '">< back</a>';
                            }
                        }
                        
                        // if we need to filter by type
                        elseif (isset($_REQUEST['lType']))
                        {
                            // get the log filter
                            $lType = $_REQUEST['lType'];

                            // try to load the list of log files for this type
                            $logFiles = array();
                            if ($handle   = opendir(LOCAL_SITE_CONFIG_BASE_LOG_PATH . $lType . '/'))
                            {
                                // loop contents
                                while (false !== ($entry = readdir($handle)))
                                {
                                    if (substr($entry, 0, 1) != '.')
                                    {
                                        $logFiles[] = $entry;
                                    }
                                }
                                closedir($handle);
                                arsort($logFiles);
                            }

                            if (COUNT($logFiles) == 0)
                            {
                                echo 'Error: Could not find any log files for that type - ' . LOCAL_SITE_CONFIG_BASE_LOG_PATH . $lType . '/<br/>';
                                echo '<br/>';
                                echo '<br/>';
                                echo '<a href="log_file_viewer.php">< back</a>';
                            }
                            else
                            {
                                // list the available logs
                                echo 'Log files within the ' . $lType . ' folder listed below, the most recent at the top. Please select one to view it\'s contents.<br/>';
                                echo '<br/><br/>';
                                echo '<ul class="adminList">';
                                $i = 0;
                                foreach ($logFiles AS $logFile)
                                {
                                    // only show the top 30
                                    if ($i > 30)
                                    {
                                        continue;
                                    }
                                    echo '<li><a href="log_file_viewer.php?lType=' . adminFunctions::makeSafe($lType) . '&lFile=' . adminFunctions::makeSafe($logFile) . '">' . adminFunctions::makeSafe($logFile) . '</a></li>';
                                    $i++;
                                }
                                echo '</ul>';
                                echo '<br/>';
                                echo '<br/>';
                                echo '<br/>';
                                echo 'Log Storage Path: ' . LOCAL_SITE_CONFIG_BASE_LOG_PATH.'<br/>';
                                echo '<br/>';
                                echo '<br/>';
                                echo '<a href="log_file_viewer.php">< back</a>';
                            }
                        }
                        else
                        {
                            // if type not selected, so first load
                            echo 'Please select the type of log to view below.<br/>';
                            echo '<br/><br/>';
                            echo '<ul class="adminList">';
                            foreach ($logFileTypes AS $logFileType)
                            {
                                echo '<li><a href="log_file_viewer.php?lType=' . adminFunctions::makeSafe($logFileType) . '">' . adminFunctions::makeSafe(UCWords(str_replace(array('-', '_'), ' ', $logFileType))) . '</a></li>';
                            }
                            echo '</ul>';
                            echo '<br/>';
                            echo '<br/>';
                            echo '<br/>';
                            echo 'Log Storage Path: ' . LOCAL_SITE_CONFIG_BASE_LOG_PATH;
                        }
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>


<?php
include_once('_footer.inc.php');
?>