<?php
define("CONTROLS_HEIGHT", 95);
$filepreviewer = 'html5_video';
$html5Player = 'jwplayer';
?>
	<style>
	.jwlogo
	{
		display: none; /* hidden as sits over volume on mp3s */
	}
	</style>
	<?php
	$jwPlayerCat = $file->extension;
	?>
	<script type="text/javascript" src="<?php echo PLUGIN_WEB_ROOT; ?>/filepreviewer/assets/players/jwplayer/jwplayer.js"></script>
	<?php
	if (isset($pluginSettings['html5_player_license_key']) && strlen($pluginSettings['html5_player_license_key']))
	{
		echo '<script type="text/javascript">jwplayer.key="' . validation::safeOutputToScreen($pluginSettings['html5_player_license_key']) . '";</script>';
		echo "\n";
	}
	?>
	
	<script type="text/javascript">
				//<![CDATA[
		$(document).ready(function() {
			jwplayer("jwPlayerContainer").setup({
				file: "<?php echo $file->generateDirectDownloadUrlForMedia(); ?>",
				type: "<?php echo $jwPlayerCat; ?>",
				title: "<?php echo validation::safeOutputToScreen($file->originalFilename); ?>",
				width: "100%",
				height: "30",
				startparam: "start",
				autostart: <?php echo $pluginSettings['preview_video_autoplay'] == 1 ? 'true' : 'false'; ?>
			});
		});
				//]]>
	</script>

<?php $imageIcon = file::getIconPreviewImageUrl((array) $file, false, 160, false, 280, 280, 'middle'); ?>
<div class="preview-download-wrapper">
	<div class="tile-stats tile-white tile-white-primary"> <img src="<?php echo $imageIcon; ?>" style="width: 140px; height: 140px;" onClick="jwplayer('jwPlayerContainer').play();"/> <h3 style="margin-bottom: 10px;" onClick="jwplayer('jwPlayerContainer').play();"><?php echo validation::safeOutputToScreen($file->originalFilename); ?></h3> <div id="jwPlayerContainer">Loading media...</div> </div>
</div>

<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
	$('.content-preview-wrapper').removeClass('loader');
});
//]]>
</script>