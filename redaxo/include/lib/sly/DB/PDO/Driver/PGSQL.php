<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_DB_PDO_Driver_PGSQL {
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
		$format = empty($this->database) ? 'pgsql:host=%s' : 'pgsql:host=%s;dbname=%s';
		return sprintf($format, $this->host, $this->database);
	}
}
