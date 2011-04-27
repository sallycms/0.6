<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Importiert die gegebene SQL-Datei in die Datenbank
 *
 * @return boolean  true bei Erfolg, sonst eine Fehlermeldung
 */
function rex_install_dump($file) {
	try {
		$dump = new sly_DB_Dump($file);
		$sql   = sly_DB_Persistence::getInstance();

		foreach ($dump->getQueries(true) as $query) {
			$sql->query($query);
		}
	}
	catch (sly_Exception $e) {
		return $e->getMessage();
	}

	return true;
}
