<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Stellt eine PDO Verbindung zur Datenbank her und hält sie vor.
 *
 * @author  zozi@webvariants.de
 * @ingroup database
 */
class sly_DB_PDO_Connection {

	private static $instances = array();

	private $driver;
	private $pdo;
	private $transrunning = false;

	private function __construct($driver, $dsn, $login, $password) {
		$this->driver = $driver;
		$this->pdo    = new PDO($dsn, $login, $password);

		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 *
	 * @return sly_DB_PDO_Connection instance
	 */
	public static function getInstance($driver, $host, $login, $password, $database) {
		if (!class_exists('sly_DB_PDO_Driver_'.strtoupper($driver))) {
			throw new sly_DB_PDO_Exception('Unbekannter Datenbank-Treiber: '.$driver);
		}

		$driverClass = 'sly_DB_PDO_Driver_'.strtoupper($driver);
		$driverObj   = new $driverClass($host, $login, $password, $database);
		$dsn         = $driverObj->getDSN();

		if (empty(self::$instances[$dsn])) {
			try {
				self::$instances[$dsn] = new self($driver, $dsn, $login, $password);
			}catch(PDOException $e) {
				throw new sly_DB_PDO_Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
			}
		}

		return self::$instances[$dsn];
	}

	public function getSQLbuilder($table) {
		$classname = 'sly_DB_PDO_SQLBuilder_'.strtoupper($this->driver);
		return new $classname($this->pdo, $table);
	}

	/**
	 *
	 * @return PDO instance
	 */
	public function getPDO() {
		return $this->pdo;
	}

	public function isTransRunning() {
		return $this->transrunning;
	}

	public function setTransRunning($bool) {
		$this->transrunning = $bool;
	}
}
