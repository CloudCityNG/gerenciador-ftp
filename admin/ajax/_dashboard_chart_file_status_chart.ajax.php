<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// pie chart of the status of items
$data       = array();
$dataForPie = $db->getRows("SELECT COUNT(1) AS total, file_status.label AS status FROM file LEFT JOIN file_status ON file.statusId = file_status.id GROUP BY file.statusId");
foreach ($dataForPie AS $dataRow)
{
	$data[] = '{ label: "' . UCWords(adminFunctions::t($dataRow['status'], $dataRow['status'])) . '",  data: ' . (int) $dataRow['total'] . '}';
}
?>
	
<script type="text/javascript">
	$(function() {
		// data
		var data = [
<?php echo implode(', ', $data); ?>
		];

		// INTERACTIVE
		$.plot($("#file_status_chart"), data,
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
						show: true
					}
				});
		$("#file_status_chart").bind("plothover", fileStatusChartHover);
		$('#wrapper_file_status_chart .background-loading').removeClass('background-loading');
	});

	function fileStatusChartHover(event, pos, obj)
	{
		if (!obj)
			return;
		percent = parseFloat(obj.series.percent).toFixed(2);
		$("#file_status_chart_hover").html('<span style="font-weight: bold; color: ' + obj.series.color + '">' + obj.series.label + ' (' + percent + '%)</span>');
	}
</script>