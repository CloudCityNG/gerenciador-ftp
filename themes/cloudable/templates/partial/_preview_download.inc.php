<?php
$imageIcon = file::getIconPreviewImageUrl((array) $file, false, 160, false, 280, 280, 'middle');

// should we show the download link
$showDownloadLink = (bool)$folder->showDownloadLinks;
if($userOwnsFile == true)
{
	// override if this user owns the file
	$showDownloadLink = true;
}
?>
<div class="preview-download-wrapper" onClick="<?php if($showDownloadLink): ?>triggerFileDownload(<?php echo (int)$file->id; ?>, '<?php echo $file->getFileHash(); ?>');<?php else: ?>alert('<?php echo str_replace("'", "\'", t('download_file_blocked', 'Downloading restricted. Please contact the file owner to request they enable downloading.')); ?>');<?php endif; ?> return false;">
	<div class="tile-stats download tile-white tile-white-primary"> <img src="<?php echo $imageIcon; ?>" style="width: 140px; height: 140px;"/><div class="icon"><i class="entypo-download"></i></div> <h3><?php if($showDownloadLink): ?><?php echo UCWords(t('account_file_details_download', 'Download')); ?>&nbsp;<?php endif; ?><?php echo validation::safeOutputToScreen($file->originalFilename); ?></h3> <p><?php echo t('file_details_filesize', 'Filesize'); ?>:&nbsp;<?php echo coreFunctions::formatSize($file->fileSize); ?></p> </div>
</div>

<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
	$('.content-preview-wrapper').removeClass('loader');
});
//]]>
</script>