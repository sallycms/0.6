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

/**
 * Dies ist eine Platzhalterimplementierung für ICache
 * Sie ist stabil.   
 * 
 */
class RedaxoCache implements ICache {
	
	private $cache;
	private $persistence;
	
    public function __construct() {
		$this->flush();
    }
    
    public function setPersistence(ICache $cache){
    	$this->persistence = $cache;
    }

    public function set($namespace, $key, $value) {
		$this->cache[$namespace][$key] = $value;
		if($this->persistence) $this->persistence->set($namespace, $key, $value);
    }

    public function get($namespace, $key, $default) {
    	if(isset($this->cache[$namespace][$key])) return $this->cache[$namespace][$key];
    	if($this->persistence) return $this->persistence->get($namespace, $key, $default);
        return $default;
    }

    public function delete($namespace, $key) {
		unset($this->cache[$namespace][$key]);
		if($this->persistence) $this->persistence->delete($namespace, $key);
    }

    public function flush() {
		$this->cache = array();
		if($this->persistence) $this->persistence->flush();
    }
}