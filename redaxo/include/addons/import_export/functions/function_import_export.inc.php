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

// Da diese Funktion im Setup direkt eingebunden wird
// hier das I18N Objekt ggf. erstellen

if ($REX['REDAXO'] && !isset($I18N)) {
	global $I18N;
	require_once(dirname(dirname(__FILE__)).'/config.inc.php');
}

/**
 * Importiert den SQL Dump $filename in die Datenbank
 *
 * @param string Pfad + Dateinamen zur SQL-Datei
 *
 * @return array Gibt ein Assoc. Array zurück.
 *               'state' => boolean (Status ob fehler aufgetreten sind)
 *               'message' => Evtl. Status/Fehlermeldung
 */
function rex_a1_import_db($filename)
{
	$importer = new sly_A1_Import_Database();
	return $importer->import($filename);
}

/**
 * Importiert das Tar-Archiv $filename in den Ordner /files
 *
 * @param string Pfad + Dateinamen zum Tar-Archiv
 *
 * @return array Gibt ein Assoc. Array zur�ck.
 *               'state' => boolean (Status ob fehler aufgetreten sind)
 *               'message' => Evtl. Status/Fehlermeldung
 */
function rex_a1_import_files($filename)
{
	$importer = new sly_A1_Import_Files();
	return $importer->import($filename);
}
