This file attempts to read the contents of "test.DNG" in the same folder and output any EXIF data:<br/>
<br/>
<?php
$file = "test.DNG";
$imagick = new Imagick();
$handle = fopen($file, 'rb');
$imagick->readImageFile($handle);
$exif = $imagick->getImageProperties("*");
foreach ($exif as $name => $val)
{
	// stop really long data
	if(COUNT($rawData) > 200)
	{
		continue;
	}
	
	// tidy name
	$name = trim(substr($name, 5));

	// limit text length just encase someone if trying to feed it invalid data
	$rawData[substr($name, 0, 200)] = substr($val, 0, 500);
}

echo "<pre>";
print_r($rawData);
echo "</pre>";