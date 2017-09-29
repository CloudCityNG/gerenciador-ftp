<?php
define('ADMIN_PAGE_TITLE', 'Dashboard');
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('_local_auth.inc.php');

// should we show a warning about lack of an encryption key
if((isset($_REQUEST['shash'])) && (!defined('_CONFIG_UNIQUE_ENCRYPTION_KEY')))
{
	// check for write permissions
	$configFile = '../_config.inc.php';
	if(!is_writable($configFile))
	{
		adminFunctions::setError("The site config file (_config.inc.php) is not writable (CHMOD 777 or 755). Please update and <a href='index.php?shash=1'>try again</a>.");
	}
	else
	{
		// try to set _config file
		$oldContent = file_get_contents($configFile);
		if(strlen($oldContent))
		{
			$newHash = coreFunctions::generateRandomString(125, "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890");
			if(strlen($newHash))
			{
				$newHashLine = "/* key used for encoding data within the site */\ndefine(\"_CONFIG_UNIQUE_ENCRYPTION_KEY\", \"".$newHash."\");\n";
				$newContent = $oldContent."\n\n".$newHashLine;
				
				// write new file contents
				$rs = file_put_contents($configFile, $newContent);
				if($rs)
				{
					adminFunctions::setSuccess("Security key set, please revert the permissions on your _config.inc.php file. If you run external file servers, please copy the new '_CONFIG_UNIQUE_ENCRYPTION_KEY' line in your _config.inc.php file onto each file server config file. The key should be the same on all servers.");
				}
			}
		}
	}
}
elseif(!defined('_CONFIG_UNIQUE_ENCRYPTION_KEY'))
{
	adminFunctions::setError("<strong>IMPORTANT:</strong> The latest code offers enhanced security by encrypting certain values before storing them within the database. The key for this needs set within your _config.inc.php file. To automatically create this, set write permissions on _config.inc.php (CHMOD 777 or 755) and <a href='index.php?shash=1'>click here</a>.");
}

include_once('_header.inc.php');

// load stats
$totalActiveFiles     = (int) $db->getValue("SELECT COUNT(1) AS total FROM file WHERE statusId = 1");
$totalDownloads       = (int) $db->getValue("SELECT SUM(visits) AS total FROM file");
$totalHDSpace         = $db->getValue("SELECT SUM(file_server.totalSpaceUsed) FROM file_server");
$totalRegisteredUsers = (int) $db->getValue("SELECT COUNT(1) AS total FROM users WHERE status='active'");
$totalPaidUsers       = (int) $db->getValue("SELECT COUNT(1) AS total FROM users WHERE status='active' AND level_id IN (SELECT id FROM user_level WHERE level_type = 'paid')");
$totalReports         = (int) $db->getValue("SELECT COUNT(1) AS total FROM file_report WHERE report_status='pending'");
$payments30Days       = $db->getRows("SELECT SUM(amount) AS total, currency_code FROM payment_log WHERE date_created BETWEEN NOW() - INTERVAL 30 DAY AND NOW() GROUP BY currency_code");

?>

<script>
// check for script upgrades
$(document).ready(function(){
    $.ajax({
        url: "ajax/check_for_upgrade.ajax.php",
        dataType: "html"
    }).done(function(response) {
        if(response.length > 0)
        {
            showInfo(response);
        }
    });
	
	loadCharts();
});

function loadCharts()
{
	$('#wrapper_14_day_chart .js_content').load('ajax/_dashboard_chart_14_day_chart.ajax.php');
	$('#wrapper_file_status_chart .js_content').load('ajax/_dashboard_chart_file_status_chart.ajax.php');
	$('#wrapper_12_months_chart .js_content').load('ajax/_dashboard_chart_12_months_chart.ajax.php');
	$('#wrapper_file_type_chart .js_content').load('ajax/_dashboard_chart_file_type_chart.ajax.php');
	$('#wrapper_14_day_users .js_content').load('ajax/_dashboard_chart_14_day_users.ajax.php');
	$('#wrapper_user_status_chart .js_content').load('ajax/_dashboard_chart_user_status_chart.ajax.php');
}
</script>

<?php if((adminFunctions::isErrors()) || (adminFunctions::isSuccess())): ?>
<div class="row clearfix" style="padding: 0px 0px 20px 0px;">
	<div class="col_12">
		<?php echo adminFunctions::compileNotifications(); ?>
	</div>
</div>
<?php endif; ?>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon largeDashboardIcon"></div>
        <div class="widget clearfix">
            <h2><?php echo adminFunctions::t('quick_overview', 'Quick Overview'); ?></h2>
            <div class="widget_inside">
                <h3>Current Statistics</h3>
                <div class="report">
                    <a href="file_manage.php">
                        <div class="button up">
                            <span class="value"><?php echo $totalActiveFiles; ?></span>
                            <span class="attr">Active Files</span>
                        </div>
                    </a>

                    <a href="<?php if($Auth->hasAccessLevel(20)): ?>server_manage.php<?php else: ?>#<?php endif; ?>">
                        <div class="button up">
                            <span class="value"><?php echo adminFunctions::formatSize($totalHDSpace, 2); ?></span>
                            <span class="attr">Space Used</span>
                        </div>
                    </a>

                    <a href="file_manage.php">
                        <div class="button up">
                            <span class="value"><?php echo $totalDownloads; ?></span>
                            <span class="attr">File Downloads</span>
                        </div>
                    </a>

                    <?php if($Auth->hasAccessLevel(20)): ?>
                    <a href="user_manage.php?filterByAccountStatus=active">
                        <div class="button up">
                            <span class="value"><?php echo $totalRegisteredUsers; ?><span class="paid-account-option">/<?php echo $totalPaidUsers; ?></span></span>
                            <span class="attr">Active<span class="paid-account-option">/Paid</span> Users</span>
                        </div>
                    </a>
                    <?php endif; ?>

                    <a href="file_report_manage.php?filterByReportStatus=pending">
                        <div class="button up">
                            <span class="value"><?php echo $totalReports; ?></span>
                            <span class="attr">Copyright Reports</span>
                        </div>
                    </a>

                    <?php if($Auth->hasAccessLevel(20)): ?>
                    <?php if(COUNT($payments30Days) == 0): ?>
                    <a href="payment_manage.php" class="paid-account-option">
                        <div class="button up">
                            <span class="value">0 <?php echo SITE_CONFIG_COST_CURRENCY_CODE; ?></span>
                            <span class="attr">30 Day Payments</span>
                        </div>
                    </a>
                    <?php else: ?>
                    <?php foreach($payments30Days AS $payments30Day): ?>
                    <a href="payment_manage.php" class="paid-account-option">
                        <div class="button up">
                            <span class="value"><?php echo number_format($payments30Day['total'], 2, '.', '').' '.$payments30Day['currency_code']; ?></span>
                            <span class="attr">30 Day Payments</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row clearfix">
    <div class="col_8">
        <div class="widget clearfix">
            <h2><?php echo adminFunctions::t("dashboard_graph_last_14_days_title", "New Files (last 14 days)"); ?></h2>
            <div id="wrapper_14_day_chart" class="widget_inside">
				<div id="14_day_chart" style="width:100%; height:300px;" class="centered background-loading"></div>
				<span class="js_content"></span>
			</div>
        </div>
    </div>
    
    <div class="col_4 last">
        <div class="widget">
            <h2><?php echo adminFunctions::t('file_status', 'File Status'); ?></h2>
            <div id="wrapper_file_status_chart" class="widget_inside">
                <div id="file_status_chart" style="width:100%; height: 300px" class="centered background-loading"></div>
                <div id="file_status_chart_hover" class="pieHoverText"></div>
				<span class="js_content"></span>
            </div>
        </div>
    </div>
</div>

<div class="row clearfix">
    <div class="col_8">
        <div class="widget clearfix">
            <h2><?php echo adminFunctions::t("dashboard_graph_last_12_months_title", "New Files (last 12 months)"); ?></h2>
            <div id="wrapper_12_months_chart" class="widget_inside">
                <div id="12_months_chart" style="width:100%; height:300px;" class="centered background-loading"></div>
                <span class="js_content"></span>
            </div>
        </div>
    </div>

    <div class="col_4 last">
        <div class="widget">
            <h2><?php echo adminFunctions::t('file_type', 'File Type'); ?></h2>
            <div id="wrapper_file_type_chart" class="widget_inside">
                <div id="file_type_chart" style="width:100%; height: 300px" class="centered background-loading"></div>
                <div id="file_type_chart_hover" class="pieHoverText"></div>
                <span class="js_content"></span>
            </div>
        </div>
    </div>
</div>

<?php if($Auth->hasAccessLevel(20)): ?>

<div class="row clearfix paid-account-option">
    <div class="col_8">
        <div class="widget clearfix">
            <h2><?php echo adminFunctions::t("dashboard_graph_user_registrations_title", "New Users (last 14 days)"); ?></h2>
            <div id="wrapper_14_day_users" class="widget_inside">
                <div id="14_day_users" style="width:100%; height:300px;" class="centered background-loading"></div>
                <span class="js_content"></span>
            </div>
        </div>
    </div>

    <div class="col_4 last">
        <div class="widget">
            <h2><?php echo adminFunctions::t('user_status', 'User Status'); ?></h2>
            <div id="wrapper_user_status_chart" class="widget_inside">
                <div id="user_status_chart" style="width:100%; height: 300px" class="centered background-loading"></div>
                <div id="user_status_chart_hover" class="pieHoverText"></div>
                <span class="js_content"></span>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
include_once('_footer.inc.php');
?>