<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * @package redaxo4
 */

function getImportDir()
{
	global $REX;
	$dir = $REX['DATAFOLDER'].'/import_export';
	if (!is_dir($dir) && !@mkdir($dir, 0777)) throw new Exception('Konnte Backup-Verzeichnis '.$dir.' nicht anlegen.');
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
