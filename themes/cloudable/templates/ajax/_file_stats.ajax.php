<?php
$file = null;
if (isset($_REQUEST['fileId']))
{
    // only keep the initial part if there's a forward slash
    $fileId = (int)$_REQUEST['fileId'];
    $file     = file::loadById($fileId);
}

// load file details
if (!$file)
{
    // if no file found, redirect to home page
    die('Could not load file.');
}

// make sure user is permitted to view stats
if ($file->canViewStats() == false)
{
	die(t("stats_error_file_statistics_are_private", "Statistics for this file are not publicly viewable."));
}

?>

<script src="<?php echo SITE_JS_PATH; ?>/charts/Chart.js"></script>
<script>
    $ = jQuery;

<?php
// last 24 hours chart
$last24hours = charts::createBarChart($file, 'last24hours');
echo $last24hours['chartJS'];

// last 7 days chart
$last7days = charts::createBarChart($file, 'last7days');
echo $last7days['chartJS'];

// last 30 days chart
$last30days = charts::createBarChart($file, 'last30days');
echo $last30days['chartJS'];

// last 12 months chart
$last12months = charts::createBarChart($file, 'last12months');
echo $last12months['chartJS'];

// top countries pie
$countries = charts::createPieChart($file, 'countries');
echo $countries['chartJS'];

// top referrers pie
$referrers = charts::createPieChart($file, 'referrers');
echo $referrers['chartJS'];

// top browsers pie
$browsers = charts::createPieChart($file, 'browsers');
echo $browsers['chartJS'];

// top os pie
$os = charts::createPieChart($file, 'os');
echo $os['chartJS'];
?>
        
    $(document).ready(function($)
    {
        redrawCharts();
    });

    $(window).resize(function()
    {
        redrawCharts();
    });

    function redrawCharts()
    {
<?php
echo $last24hours['onLoadJS'];
echo $last7days['onLoadJS'];
echo $last30days['onLoadJS'];
echo $last12months['onLoadJS'];
echo $countries['onLoadJS'];
echo $referrers['onLoadJS'];
echo $browsers['onLoadJS'];
echo $os['onLoadJS'];
?>
    }

    function showChart(chartId)
    {
        $('#tab1_chart1').hide();
        $('#tab1_chart2').hide();
        $('#tab1_chart3').hide();
        $('#tab1_chart4').hide();
        $('#' + chartId).show(0, function() {
            redrawCharts();
        });

        return false;
    }
</script>

<div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><?php echo validation::safeOutputToScreen($file->originalFilename).' '.t("stats_title", "statistics"); ?>. (<?php echo validation::safeOutputToScreen($file->visits).' '.t("stats_downloads", "Downloads"); ?>)</h4>
    </div>

    <div class="modal-body">
        <div class="row">
		
			<div class="col-md-3">
				<div class="modal-icon-left"><img src="<?php echo SITE_IMAGE_PATH; ?>/modal_icons/chart_2.png"/></div>
			</div>
			
			<div class="col-md-9">
				<div id="tabs" class="tab-content">
					<ul class="nav nav-tabs stats-padding">
						<li class="active"><a href="#tab1" data-toggle="tab"><i class="fa fa-user tab-padding">&nbsp;</i><?php echo t("visitors", "visitors"); ?></a></li>
						<li><a href="#tab2" data-toggle="tab"><i class="fa fa-globe tab-padding">&nbsp;</i><?php echo t("countries", "countries"); ?></a></li>
						<li><a href="#tab3" data-toggle="tab"><i class="fa fa-comment tab-padding">&nbsp;</i><?php echo t("top_referrers", "top referrers"); ?></a></li>
						<li><a href="#tab4" data-toggle="tab"><i class="fa fa-laptop tab-padding">&nbsp;</i><?php echo t("browsers", "browsers"); ?></a></li>
						<li><a href="#tab5" data-toggle="tab"><i class="fa fa-linux tab-padding">&nbsp;</i><?php echo t("operating_systems", "operating systems"); ?></a></li>
					</ul>  
					<div id="tab1" class="tab-pane active">
						<!-- TAB 1 -->
						<div class="text-center">
							<a href="#" onClick="showChart('tab1_chart1');
					return false;"><?php echo t("last_24_hours", "last 24 hours"); ?></a> | <a href="#" onClick="showChart('tab1_chart2');
					return false;"><?php echo t("last_7_days", "last 7 days"); ?></a> | <a href="#" onClick="showChart('tab1_chart3');
					return false;"><?php echo t("last_30_days", "last 30 days"); ?></a> | <a href="#" onClick="showChart('tab1_chart4');
					return false;"><?php echo t("last_12_months", "last 12 months"); ?></a><br/><br/>
						</div>
						<div id="tab1_chart1">
							<div class="responsiveTable"><?php echo $last24hours['canvasHTML']; ?>
								<div> 
									<?php echo $last24hours['dataTableHTML']; ?>
								</div>
							</div>
						</div>
						<div id="tab1_chart2" style="display:none;">
							<?php echo $last7days['canvasHTML']; ?>
							<div> 
								<?php echo $last7days['dataTableHTML']; ?>
							</div>
						</div>
						<div id="tab1_chart3" style="display:none;">
							<?php echo $last30days['canvasHTML']; ?>
							<div> 
								<?php echo $last30days['dataTableHTML']; ?>
							</div>
						</div>
						<div id="tab1_chart4" style="display:none;">
							<?php echo $last12months['canvasHTML']; ?>
							<div> 
								<?php echo $last12months['dataTableHTML']; ?>
							</div>
						</div>
					</div>
					<div id="tab2" class="tab-pane">
						<?php echo $countries['canvasHTML']; ?>
						<div> 
							<?php echo $countries['dataTableHTML']; ?>
						</div>
					</div>
					<div id="tab3" class="tab-pane">
						<?php echo $referrers['canvasHTML']; ?>
						<div> 
							<?php echo $referrers['dataTableHTML']; ?>
						</div>
					</div>
					<div id="tab4" class="tab-pane">
						<?php echo $browsers['canvasHTML']; ?>
						<div> 
							<?php echo $browsers['dataTableHTML']; ?>
						</div>
					</div>
					<div id="tab5" class="tab-pane">
						<?php echo $os['canvasHTML']; ?>
						<div> 
							<?php echo $os['dataTableHTML']; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>

    <div class="modal-footer">
        <input type="hidden" name="submitme" id="submitme" value="1"/>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo t("close", "close"); ?></button>
    </div>