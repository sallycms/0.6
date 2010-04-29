<?php

function getImportDir()
{
	global $REX;
	return $REX['INCLUDE_PATH'].'/addons/import_export/backup';
}

function compareFiles($file_a, $file_b)
{
	$dir    = getImportDir();
	$time_a = filemtime($dir.'/'.$file_a); 
	$time_b = filemtime($dir.'/'.$file_b); 

	if ($time_a == $time_b) {
		return 0;
	}
	return ($time_a > $time_b) ? -1 : 1;
}

function readImportFolder($fileprefix)
{
	$folder = readFilteredFolder(getImportDir(), $fileprefix);
	usort($folder, 'compareFiles');
	return $folder;
}
