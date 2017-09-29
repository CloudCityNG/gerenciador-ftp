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
	<?php if ($file->extension == 'webm'): ?>
		<!-- for testing IE support with webm files -->
		<script type="text/javascript">
			function videoFail(vid)
			{
				var ua = window.navigator.userAgent;
				var msie = ua.indexOf("MSIE ");
				if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))
				{
					//	some complications so that in IE9 we offer to install WebM
					$('#videoFailIECheck').show();
					$('#iEWebmSupport').show();
					$('#jwPlayerContainer').remove();
					$('#jplayer_container').remove();
					$('#videoFailText').html(getMediaErrorString(vid));
				}
			}

			function getMediaErrorString(vid)
			{
				try {
					switch (vid.error.code) {
						case vid.error.MEDIA_ERR_ABORTED:
							$('#iEWebmSupport').hide();
							return 'You aborted the video playback.';
						case vid.error.MEDIA_ERR_NETWORK:
							$('#iEWebmSupport').hide();
							return 'A network error caused the video download to fail part-way.';
						case vid.error.MEDIA_ERR_DECODE:
							return 'The video playback was aborted due to a corruption problem or because the video used features your browser did not support.';
						case vid.error.MEDIA_ERR_SRC_NOT_SUPPORTED:
							return 'The video could not be loaded, either because the server or network failed or because the format is not supported.';
						default:
							return 'An unknown error occurred.';
					}
				}
				catch (exp) {
					return 'Your browser does not fully implement the HTML5 video element.';
				}
			}
		</script>
		<div style="display: none;">
			<video src="<?php echo $file->generateDirectDownloadUrlForMedia(); ?>" controls preload="metadata" onerror="videoFail(this)"></video>
		</div>
	<?php endif; ?>
	<?php
	$jwPlayerCat = $file->extension;
	switch ($file->extension)
	{
		case 'm4v':
			$jwPlayerCat = 'mp4';
			break;
		case 'ogg':
			$jwPlayerCat = 'webm';
			break;
	}
	?>

	<script type="text/javascript" src="<?php echo PLUGIN_WEB_ROOT; ?>/filepreviewer/assets/players/jwplayer/jwplayer.js"></script>
	<?php
	if (isset($pluginSettings['html5_player_license_key']) && strlen($pluginSettings['html5_player_license_key']))
	{
		echo '<script type="text/javascript">jwplayer.key="' . validation::safeOutputToScreen($pluginSettings['html5_player_license_key']) . '";</script>';
		echo "\n";
	}
	?>

	<div id="videoFailIECheck" style="display: none; vertical-align: middle; text-align: center; background-color: #dedede; padding: 30px;">
		<a id="iEWebmSupport" href="https://tools.google.com/dlpage/webmmf/" target="_blank">
			<img style="border: none" alt="Install WebM support from webmproject.org" src="<?php echo PLUGIN_WEB_ROOT; ?>/filepreviewer/assets/img/Install-WebM-Support.png" />
		</a>
		<br/><br/><span id="videoFailText">WebM Support Required</span>
	</div>
	<div id="jwPlayerContainer">Loading media...</div>
	<script type="text/javascript">
	//<![CDATA[
	$(document).ready(function() {
		<?php $downloadUrlForMedia = $file->generateDirectDownloadUrlForMedia(); ?>
		jwplayer("jwPlayerContainer").setup({
			file: "<?php echo $downloadUrlForMedia; ?>",
			type: "<?php echo $jwPlayerCat; ?>",
			title: "<?php echo validation::safeOutputToScreen($file->originalFilename); ?>",
			width: "100%",
			startparam: "start",
			abouttext: '<?php echo str_replace("'", "\'", SITE_CONFIG_SITE_NAME); ?>',
			aboutlink: '<?php echo str_replace("'", "\'", $file->getFullShortUrl()); ?>',
			sharing: {
			link: '<?php echo str_replace("'", "\'", $file->getFullShortUrl()); ?>'
			},
			logo: {
			file: '<?php echo SITE_IMAGE_PATH; ?>/main_logo.jpg',
					link: '<?php echo coreFunctions::getCoreSitePath(); ?>',
					linktarget: '_blank',
					hide: 'false'
			},
			aspectratio: "16:9",
			image: "<?php echo file::getIconPreviewImageUrl((array)$file, false, 160, false, 640, 320); ?>",
			autostart: <?php echo $pluginSettings['preview_video_autoplay'] == 1 ? 'true' : 'false'; ?>
		});
	});
	//]]>
</script>