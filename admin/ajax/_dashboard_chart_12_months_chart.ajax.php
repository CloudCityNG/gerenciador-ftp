<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// last 12 months files
$tracker      = 12;
$last12Months = array();
while ($tracker >= 0)
{
    $date                = date("Y-m", strtotime("-" . $tracker . " month"));
    $last12Months[$date] = 0;
    $tracker--;
}

$tracker = 1;
$data    = array();
$label   = array();

// get data
$chartData = $db->getRows("SELECT COUNT(1) AS total, MID(uploadedDate, 1, 7) AS date_part FROM file WHERE file.uploadedDate >= DATE_ADD(CURDATE(), INTERVAL -13 MONTH) GROUP BY MONTH(uploadedDate)");

// format data for easier lookups
$chartDataArr = array();
if($chartData)
{
	foreach($chartData AS $chartDataItem)
	{
		$chartDataArr[$chartDataItem{'date_part'}] = $chartDataItem['total'];
	}
}

// prepare for table
foreach ($last12Months AS $k => $total)
{
    $totalFiles = isset($chartDataArr[$k])?$chartDataArr[$k]:0;
    $data[]     = '[' . $tracker . ',' . (int) $totalFiles . ']';
    $label[]    = '[' . $tracker . ',\'' . date('M y', strtotime($k)) . '\']';
    $tracker++;
}
?>
	
<script type="text/javascript">
	$(function() {
		var css_id = "#12_months_chart";
		var data = [
			{label: '<?php echo UCWords(adminFunctions::t("files", "files")); ?>', data: [<?php echo implode(", ", $data); ?>]}
		];
		var options = {
			series: {stack: 0,
				lines: {show: false, steps: false},
				grid: {backgroundColor: {colors: ["#fff", "#eee"]}},
				bars: {show: true, barWidth: 0.9, align: 'center'}},
			xaxis: {ticks: [<?php echo implode(", ", $label); ?>]},
			colors: ["#55A9D3"]
		};

		$.plot($(css_id), data, options);
		$('#wrapper_12_months_chart .background-loading').removeClass('background-loading');
	});
</script>