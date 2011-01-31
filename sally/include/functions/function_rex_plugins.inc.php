<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Plugin-Funktionen
 *
 * @package redaxo4
 */

function rex_plugins_folder($addon, $plugin = null) {
	$addonFolder = rex_addons_folder($addon);
	return sly_Util_Directory::join($addonFolder, 'plugins', $plugin);
}

function rex_plugins_file() {
	trigger_error('SallyCMS does not have a specific plugins file.', E_USER_WARNING);
	return sly_Util_Directory::join(SLY_DYNFOLDER, 'internal', 'sally', 'plugins.rexcompat.php');
}

function rex_read_plugins_folder($addon, $folder = '') {
	if (empty($folder)) {
		$folder = rex_plugins_folder($addon);
	}

	$directory = new sly_Util_Directory($folder);
	return $directory->exists() ? $directory->listPlain(false, true) : array();
}
