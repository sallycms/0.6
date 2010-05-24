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
	
	private function __construct() {}
	
	public static function getInstance() {
		if (empty(self::$instance)) self::$instance = new self();
		return self::$instance;
	}
	
	public function set($key, $value) {
		
	}
	
	public function get($key) {
		
	}
	
	public function has($key) {
		
	}
	
	public function remove($key) {
		
	}
	
}