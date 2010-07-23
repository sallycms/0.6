<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_DB_PDO_Driver_OCI {
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
		$format = empty($this->database) ? 'oci:host=%s' : 'oci:host=%s;dbname=%s';
		return sprintf($format, $this->host, $this->database);
	}
}
