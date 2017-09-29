<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// pie chart of user status
$data       = array();
$dataForPie = $db->getRows("SELECT COUNT(1) AS total, user_level.label FROM users LEFT JOIN user_level ON users.level_id = user_level.id GROUP BY users.level_id ORDER BY COUNT(users.id) DESC");
foreach ($dataForPie AS $dataRow)
{
	$data[] = '{ label: "' . UCWords(adminFunctions::t($dataRow['label'], $dataRow['label'])) . '",  data: ' . (int) $dataRow['total'] . '}';
}
?>
	
<script type="text/javascript">
	$(function() {
		// data
		var data = [
<?php echo implode(', ', $data); ?>
		];

		// INTERACTIVE
		$.plot($("#user_status_chart"), data,
				{
					series: {
						pie: {
							show: true
						}
					},
					grid: {
						hoverable: true,
						clickable: true
					},
					legend: {
						show: false
					}
				});
		$("#user_status_chart").bind("plothover", userStatusChartHover);
		$('#wrapper_user_status_chart .background-loading').removeClass('background-loading');
	});

	function userStatusChartHover(event, pos, obj)
	{
		if (!obj)
			return;
		percent = parseFloat(obj.series.percent).toFixed(2);
		$("#user_status_chart_hover").html('<span style="font-weight: bold; color: ' + obj.series.color + '">' + obj.series.label + ' (' + percent + '%)</span>');
	}
</script>