<?php

function getImportDir()
{
	global $REX;
	$dir = $REX['DATAFOLDER'].'/import_export';
	if (!is_dir($dir) && !@mkdir($dir, 0777)) throw new Exception('Konnte Backup-Verzeichnis '.$dir.' nicht anlegen.');

	if (!file_exists($dir.'/.htaccess')) {
		$htaccess = "order deny,allow\ndeny from all";
		$written  = @file_put_contents($dir.'/.htaccess', $htaccess) > 0;

		if (!$written) {
			throw new Exception('Konnte Backup-Verzeichnis '.$dir.' nicht gegen HTTP-Zugriffe schützen.');
		}
	}

	return $dir;
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
