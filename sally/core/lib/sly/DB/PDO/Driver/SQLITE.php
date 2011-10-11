<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup database
 */
class sly_DB_PDO_Driver_SQLITE extends sly_DB_PDO_Driver {
	/**
	 * @throws sly_DB_PDO_Exception  when the database file could not be created
	 * @return string
	 */
	public function getDSN() {
		if(empty($this->database)) return 'sqlite::memory:';

		$dbFile = sly_Util_Directory::join(SLY_DYNFOLDER, 'internal'.DIRECTORY_SEPARATOR.'sally'.DIRECTORY_SEPARATOR.'sqlite', preg_replace('#[^a-z0-9-_.,]#i', '_', $this->database).'.sq3');
		if (!is_dir(dirname($dbFile)) && !@mkdir(dirname($dbFile), sly_Core::getDirPerm(), true)) throw new sly_DB_PDO_Exception('Konnte Datenverzeichnis für Datenbank '.$this->database.' nicht erzeugen.');
		return 'sqlite:'.$dbFile;
	}

	/**
	 * @throws sly_DB_PDO_Exception  always
	 * @param  string $name          the database name
	 * @throws sly_DB_PDO_Exception  always
	 */
	public function getCreateDatabaseSQL($name) {
		throw new sly_DB_PDO_Exception('Creating databases by SQL is not meaningful in SQLite.');
	}
}
