<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_DB_PDO_Driver_SQLITE {
	protected $host;
	protected $login;
	protected $password;
	protected $database;

	public function __construct($host, $login, $password, $database) {
		$this->host     = $host;
		$this->login    = $login;
		$this->password = $password;
		$this->database = $database;
	}

	public function getDSN() {
		$dbFile = sly_Util_Directory::join(SLY_DYNFOLDER, 'internal/sally/sqlite', preg_replace('#[^a-z0-9-_.,]#i', '_', $this->database).'.sq3');
		if (!is_dir(dirname($dbFile)) && !@mkdir(dirname($dbFile), 0777, true)) throw new sly_DB_PDO_Exception('Konnte Datenverzeichnis für Datenbank '.$this->database.' nicht erzeugen.');
		$format = empty($this->database) ? 'sqlite::memory:' : 'sqlite:%s';
		return sprintf($format, $dbFile);
	}
}
