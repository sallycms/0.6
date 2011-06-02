<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Sonstige Funktionen
 *
 * @package redaxo4
 */

/**
 * Funktion zum Anlegen eines Sprache-Objekts
 *
 * @param  string  $locale      Locale der Sprache
 * @param  string  $searchpath  Pfad zum Ordner indem die Sprachdatei gesucht werden soll
 * @param  boolean $setlocale   true, wenn die locale für die Umgebung gesetzt werden soll, sonst false
 * @return sly_I18N
 */
function rex_create_lang($locale = 'de_de', $searchpath = '', $setlocale = true) {
	global $REX;

	$_searchpath = $searchpath;

	if (empty($searchpath)) {
		$searchpath = sly_Util_Directory::join(SLY_SALLYFOLDER, 'backend', 'lang');
	}

	$lang_object = new sly_I18N($locale, $searchpath);

	if (empty($_searchpath)) {
		$REX['LOCALES'] = $lang_object->getLocales($searchpath);
	}

	if ($setlocale) {
		sly_set_locale($lang_object);
	}

	return $lang_object;
}
