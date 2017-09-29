<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// last 14 days user registrations
$tracker    = 14;
$last14Days = array();
while ($tracker >= 0)
{
    $date              = date("Y-m-d", strtotime("-" . $tracker . " day"));
    $last14Days[$date] = 0;
    $tracker--;
}

$tracker  = 1;
$dataFree = array();
$dataPaid = array();
$label    = array();

// get data
$chartData1 = $db->getRows("SELECT COUNT(1) AS total, MID(datecreated, 1, 10) AS date_part FROM users WHERE users.datecreated >= DATE_ADD(CURDATE(), INTERVAL -15 DAY) AND level_id IN (SELECT id FROM user_level WHERE level_type = 'free') GROUP BY DAY(datecreated)");

// format data for easier lookups
$chartDataArr1 = array();
if($chartData1)
{
	foreach($chartData1 AS $chartDataItem1)
	{
		$chartDataArr1[$chartDataItem1{'date_part'}] = $chartDataItem1['total'];
	}
}

// get data
$chartData2 = $db->getRows("SELECT COUNT(1) AS total, MID(datecreated, 1, 10) AS date_part FROM users WHERE users.datecreated >= DATE_ADD(CURDATE(), INTERVAL -15 DAY) AND level_id IN (SELECT id FROM user_level WHERE level_type = 'paid') GROUP BY DAY(datecreated)");

// format data for easier lookups
$chartDataArr2 = array();
if($chartData2)
{
	foreach($chartData2 AS $chartDataItem2)
	{
		$chartDataArr2[$chartDataItem2{'date_part'}] = $chartDataItem2['total'];
	}
}

// prepare for table
foreach ($last14Days AS $k => $total)
{
	$totalUsers = isset($chartDataArr1[$k])?$chartDataArr1[$k]:0;
    $dataFree[] = '[' . $tracker . ',' . (int) $totalUsers . ']';
    $totalUsers = isset($chartDataArr2[$k])?$chartDataArr2[$k]:0;
    $dataPaid[] = '[' . $tracker . ',' . (int) $totalUsers . ']';
    $label[]    = '[' . $tracker . ',\'' . date('jS', strtotime($k)) . '\']';
    $tracker++;
}
?>
	
<script type="text/javascript">
	$(function() {
		var css_id = "#14_day_users";
		var data = [
			{label: '<?php echo UCWords(adminFunctions::t("free_user", "free user")); ?>', data: [<?php echo implode(", ", $dataFree); ?>]},
			{label: '<?php echo UCWords(adminFunctions::t("paid_user", "paid user")); ?>', data: [<?php echo implode(", ", $dataPaid); ?>]}
		];
		var options = {
			series: {stack: 0,
				lines: {show: false, steps: false},
				grid: {backgroundColor: {colors: ["#fff", "#eee"]}},
				bars: {show: true, barWidth: 0.9, align: 'center'}},
			xaxis: {ticks: [<?php echo implode(", ", $label); ?>]},
			colors: ["#55A9D3", "#4DA74D"]
		};

		$.plot($(css_id), data, options);
		$('#wrapper_14_day_users .background-loading').removeClass('background-loading');
	});
</script>