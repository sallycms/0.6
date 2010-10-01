<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Addon Funktionen
 *
 * @package redaxo4
 */

function rex_addons_folder($addon = null) {
	$service = sly_Service_Factory::getService('AddOn');
	return $service->baseFolder($addon);
}

function rex_read_addons_folder($folder = null) {
	if ($folder === null) {
		$folder = rex_addons_folder();
	}

	$directory = new sly_Util_Directory($folder);
	return $directory->exists() ? $directory->listPlain(false, true) : array();
}

// ------------------------------------- Helpers

/**
 * Importiert die gegebene SQL-Datei in die Datenbank
 *
 * @return boolean  true bei Erfolg, sonst eine Fehlermeldung
 */
function rex_install_dump($file) {
	try {
		$dump = new sly_DB_Dump($file);
	}
	catch (sly_Exception $e) {
		return $e->getMessage();
	}

	$error = '';
	$sql   = rex_sql::getInstance();

	foreach ($dump->getQueries(true) as $query) {
		$sql->setQuery($query);
		$sqlerr = $sql->getError();

		if (!empty($sqlerr)) {
			$error .= $sqlerr."<br />\n";
		}
	}

	return $error == '' ? true : $error;
}
