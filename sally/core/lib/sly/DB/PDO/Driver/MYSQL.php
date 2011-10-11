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
class sly_DB_PDO_Driver_MYSQL extends sly_DB_PDO_Driver {
	/**
	 * @return string
	 */
	public function getDSN() {
		$dsn = 'mysql:host='.$this->host;
		if(!empty($this->database)) $dsn .=';dbname='.$this->database;
		return $dsn;
	}

	/**
	 * @param  string $name  the database name
	 * @return string
	 */
	public function getCreateDatabaseSQL($name) {
		return 'CREATE DATABASE `'.$name.'` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
	}
}
