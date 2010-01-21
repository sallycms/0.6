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

class FileCache implements ICache{
	
	private $cachepath;
	private $cache;
	
	public function __construct(){
		$this->cachepath = FILECACHE_PATH.'generated/';
		$this->cache = array();
		
	}
	
	public function set($namespace, $key, $value){
		if(!isset($this->cache[$namespace]))
			$this->readFile($namespace);
		$this->cache[$namespace][$key] = serialize($value);
		$this->writeFile($namespace);
	}
	
	public function get($namespace, $key, $default){
		if(!isset($this->cache[$namespace]))
			$this->readFile($namespace);				
		if(isset($this->cache[$namespace][$key])){
			return unserialize($this->cache[$namespace][$key]);
		}
		return $default;
	}
	
	public function delete($namespace, $key){
		if(!isset($this->cache[$namespace]))
			$this->readFile($namespace);
		unset($this->cache[$namespace][$key]);
		$this->writeFile($namespace);
	}
	
	public function flush(){
		self::flushstatic();
	}
	
	public static function flushstatic(){
		rex_deleteDir(FILECACHE_PATH.DIRECTORY_SEPARATOR .'generated', false);
	}
		
	/**
	 * serialisiert einen string, für die performance 
	 * auch als json string falls PHP5 vorhanden ist
	 * @param $content mixed object
	 * @return encoded string
	 */
	function cache_encode($content){
		global $REX;
		if(function_exists('json_encode') && strpos($REX['LANG'], 'utf8')){
			return json_encode($content);
		}else{
			return serialize($content);
		}
	}
	
	/**
	 * deserialisiert einen string, für die performance
	 * auch von json string falls PHP5 vorhanden ist
	 * @param $content encoded string
	 * @return mixed decoded object
	 */
	function cache_decode($content){
		global $REX;
		if(function_exists('json_decode') && strpos($REX['LANG'], 'utf8')){
			return json_decode($content, true);
		}else{
			return unserialize($content);
		}
	}
		
	private function readFile($varname){
		$cacheFile = $this->cachepath.$varname.'.cache';
		if (file_exists($cacheFile)){ 
			$this->cache[$varname] = $this->cache_decode(file_get_contents($cacheFile));
		}
	}
	
	private function writeFile($varname){
		$cacheFile = $this->cachepath.$varname.'.cache';
		file_put_contents($cacheFile, $this->cache_encode($this->cache[$varname]));
	}	
}