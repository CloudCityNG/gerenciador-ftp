<?php
// check filesize
if ($file->fileSize >= 10485760) // 10MB
{
	echo t('document_can_not_be_previewed', '- Document can not be previewed as it is too big. Please download the file to view it.');
}
else
{
	?>
	<iframe src="https://docs.google.com/gview?url=<?php echo $file->generateDirectDownloadUrlForMedia(); ?>&embedded=true" height="700" width="100%" frameborder="0" style="border: 0px solid #ddd;" class="background-loader"></iframe>
	<?php
}