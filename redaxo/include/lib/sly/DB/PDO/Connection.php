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

/**
 * Stellt eine PDO Verbindung zur Datenbank her und hält sie vor.  
 * 
 * @author zozi@webvariants.de
 *
 */
class sly_DB_PDO_Connection {
	
	private static $instances = array();

	private $driver;
	private $pdo;
	private $transrunning = false; 
	
	private function __construct($driver, $connString, $login, $password) {
		$this->driver = $driver;
		$this->pdo    = new PDO($driver.':'.$connString, $login, $password);
	}
	
	/**
	 * 
	 * @return sly_DB_PDO_Connection instance
	 */
	public static function getInstance($driver, $connString, $login, $password) {
		if (empty(self::$instances[$driver.$connString])) {
			self::$instances[$driver.$connString] = new self($driver, $connString, $login, $password);
		}
		
		return self::$instances[$driver.$connString];
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
