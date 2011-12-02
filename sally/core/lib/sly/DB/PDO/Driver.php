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
abstract class sly_DB_PDO_Driver {
	protected $host;     ///< string
	protected $login;    ///< string
	protected $password; ///< string
	protected $database; ///< string

	public static $drivers = array('mysql', 'oci', 'pgsql', 'sqlite'); ///< array

	/**
	 * @param string $host
	 * @param string $login
	 * @param string $password
	 * @param string $database
	 */
	public function __construct($host, $login, $password, $database) {
		$this->host     = (string)$host;
		$this->login    = (string)$login;
		$this->password = (string)$password;
		$this->database = (string)$database;
	}

	/**
	 * @return array
	 */
	public function getPDOOptions() {
		return array();
	}

	/**
	 * @return array
	 */
	public function getPDOAttributes() {
		return array();
	}

	/**
	 * @return array
	 */
	public static function getAvailable() {
		return array_intersect(self::$drivers, PDO::getAvailableDrivers());
	}

	/**
	 * @return string
	 */
	abstract public function getDSN();

	/**
	 * @param  string $name  the database name
	 * @return string
	 */
	abstract public function getCreateDatabaseSQL($name);
}
