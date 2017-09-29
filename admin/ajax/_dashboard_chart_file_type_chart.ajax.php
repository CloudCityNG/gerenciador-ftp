<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// pie chart of file types
$data       = array();
$dataForPie = $db->getRows("SELECT COUNT(1) AS total, file.extension AS status FROM file WHERE statusId=1 GROUP BY file.extension ORDER BY COUNT(1) DESC");
$counter    = 1;
$otherTotal = 0;
foreach ($dataForPie AS $dataRow)
{
	if ($counter > 10)
	{
		$otherTotal = $otherTotal + $dataRow['total'];
	}
	else
	{
		$data[] = '{ label: "' . strtolower(adminFunctions::t($dataRow['status'], $dataRow['status'])) . '",  data: ' . (int) $dataRow['total'] . '}';
	}
	$counter++;
}
if ($otherTotal > 0)
{
	$data[] = '{ label: "' . strtolower(adminFunctions::t('other', 'other')) . '",  data: ' . (int) $otherTotal . '}';
}
?>
	
<script type="text/javascript">
	$(function() {
		// data
		var data = [
<?php echo implode(', ', $data); ?>
		];

		// INTERACTIVE
		$.plot($("#file_type_chart"), data,
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
		$("#file_type_chart").bind("plothover", fileTypeChartHover);
		$('#wrapper_file_type_chart .background-loading').removeClass('background-loading');
	});

	function fileTypeChartHover(event, pos, obj)
	{
		if (!obj)
			return;
		percent = parseFloat(obj.series.percent).toFixed(2);
		$("#file_type_chart_hover").html('<span style="font-weight: bold; color: ' + obj.series.color + '">' + obj.series.label + ' (' + percent + '%)</span>');
	}
</script>