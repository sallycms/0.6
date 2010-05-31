<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_Registry_Persistent implements sly_Registry_Registry {

	private static $instance;
	private $store;
	private $pdo;
	private $prefix;
	
	private function __construct() {
		$this->store  = new sly_Util_Array();
		$this->pdo    = sly_DB_PDO_Persistence::getInstance();
		$this->prefix = sly_Core::config()->get('TABLE_PREFIX');
	}
	
	public static function getInstance() {
		if (empty(self::$instance)) self::$instance = new self();
		return self::$instance;
	}
	
	public function set($key, $value) {
		$qry = 'REPLACE INTO '.$this->prefix.'registry (`key`, `value`) VALUES (?,?)';
		$this->pdo->query($qry, array($key, serialize($value)));
		
		return $this->store->set($key, $value);
	}
	
	public function get($key) {
		if (!$this->store->has($key)) {
			$value = $this->getValue($key);
			
			if ($value !== false) {
				$value = unserialize($value);
				$this->store->set($key, $value);
				return $value; // schneller als nochmal get() aufzurufen
			}
			
			// fallthrough -> Fehlerbehandlung durch sly_Util_Array
		}
		
		return $this->store->get($key);
	}
	
	public function has($key) {
		if ($this->store->has($key)) return true;
		
		$value = $this->getValue($key);
		
		if ($value !== false) {
			$value = unserialize($value);
			$this->store->set($key, $value);
			return true;
		}
		
		return false;
	}
	
	public function remove($key) {
		$this->pdo->query('DELETE FROM '.$this->prefix.'registry WHERE `key` = ?', array($key));
		return $this->store->remove($key);
	}
	
	protected static function getValue($key) {
		return $this->pdo->magicFetch('`value`', 'registry', '`key` = ?', array($key));
	}
}