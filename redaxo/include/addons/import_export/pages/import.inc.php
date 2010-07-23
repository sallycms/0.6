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

$info     = '';
$warning  = '';
$function = rex_request('function', 'string');
$filename = rex_request('file', 'string');
$baseDir  = getImportDir().'/';

if (!empty($filename)) {
	$filename = str_replace('/', '', $filename);
	$fileInfo = sly_A1_Helper::getFileInfo($baseDir.$filename);

	if (!$fileInfo['exists']) {
		$warning  = 'Die ausgewählte Datei existiert nicht.';
		$filename = '';
		$function = '';
	}
	elseif ($function == 'dbimport' && $fileInfo['type'] != 'sql') {
		$filename = '';
		$function = '';
	}
	elseif ($function == 'fileimport' && $fileInfo['type'] != 'tar') {
		$filename = '';
		$function = '';
	}
}

$importer = null;

// Funktionen abarbeiten

if ($function == 'delete') {
	if (unlink($baseDir.$filename)) $info = $I18N->msg('im_export_file_deleted');
	else $warning = 'Die Datei könnte nicht gelöscht werden.';
}
elseif ($function == 'dbimport') {
	$importer = new sly_A1_Import_Database();
}
elseif ($function == 'fileimport') {
	$importer = new sly_A1_Import_Files();
}

if ($importer) {
	$retval = $importer->import($baseDir.$filename);

	if ($retval['state']) $info = $retval['message'];
	else $warning = $retval['message'];
}

// View anzeigen

include $REX['INCLUDE_PATH'].'/addons/import_export/templates/import.phtml';
