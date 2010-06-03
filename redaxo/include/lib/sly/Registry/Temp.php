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

class sly_Registry_Temp implements sly_Registry_Registry {
	
	private static $instance;
	
	private $store;
	
	private function __construct() {
		$this->store = new sly_Util_Array();
	}
	
	public static function getInstance() {
		if (empty(self::$instance)) self::$instance = new self();
		return self::$instance;
	}
		
	public function set($key, $value) {
		return $this->store->set($key, $value);
	}
	
	public function get($key) {
		return $this->store->get($key);
	}
	
	public function has($key) {
		return $this->store->has($key);
	}
	
	public function remove($key) {
		return $this->store->remove($key);
	}
	
}
