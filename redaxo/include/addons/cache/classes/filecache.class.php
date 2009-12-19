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
	private $article;
	private $category;
	private $alist;
	private $clist;
	
	public function __construct($cachepath){
		$this->cachepath = $cachepath;
		$this->article = $this->category = $this->alist = $this->clist = array();
		
	}
	
	public function set($key, $value){
		$type = explode('_', $key);
		if($type[0] == 'article' || $type[0] == 'category'){
			return $this->setRedaxo($type, $value);
		}elseif($type[0] == 'alist'){
			return $this->setAlist($type, $value);
		}elseif($type[0] == 'clist'){
			return $this->setClist($type, $value);
		}
	}
	
	public function get($key, $default){
		$type = explode('_', $key);
		if($type[0] == 'article' || $type[0] == 'category'){
			return $this->getRedaxo($type, $default);
		}elseif($type[0] == 'alist'){
			return $this->getAlist($type, $default);
		}elseif($type[0] == 'clist'){
			return $this->getClist($type, $default);
		}
	}
	
	public function delete($key){
		$type = explode('_', $key);
		if($type[0] == 'article' || $type[0] == 'category'){
			return $this->deleteRedaxo($type);
		}elseif($type[0] == 'alist'){
			return $this->deleteAlist($type);
		}elseif($type[0] == 'clist'){
			return $this->deleteClist($type);
		}
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
	
	private function setRedaxo($params, $value) {
		$var = $this->$params[0];
		if(empty($var))
			$this->readFile($params[0]);
		$var = $this->$params[0];
		$var[$params[1]][$params[2]] = serialize($value);
		$this->$params[0] = $var;
		$this->writeFile($params[0]);
	}

	private function getRedaxo($params, $default) {
		
		if(empty($this->$params[0]))
			$this->readFile($params[0]);				
		$var = $this->$params[0];
		if(isset($var[$params[1]][$params[2]])){
			return unserialize($var[$params[1]][$params[2]]);
		}
		return $default;
	}
	
	private function deleteRedaxo($params){
		$this->readFile($params[0]);
		$var = $this->$params[0];
		unset($var[$params[1]][$params[2]]);
		$this->$params[0] = $var;
		$this->writeFile($params[0]);
		if($params[0] == 'category') $this->deleteRedaxo(array('article', $params[1], $params[2]));
	}
	
	private function setAlist($params, $value) {
		if(empty($this->alist))
			$this->readFile('alist');
		$this->alist[$params[1]][$params[2]] = $value;
		$this->writeFile('alist');
	}

	private function getAlist($params, $default) {
		if(empty($this->alist))
			$this->readFile('alist');				
		if(isset($this->alist[$params[1]][$params[2]])){
			return $this->alist[$params[1]][$params[2]];
		}
		return $default;
	}
	
	private function deleteAlist($params){
		if(empty($this->alist))
			$this->readFile('alist');
		unset($this->alist[$params[1]][$params[2]]);
		$this->writeFile('alist');
	}
	
	private function setClist($params, $value) {
		if(empty($this->clist))
			$this->readFile('clist');
		$this->clist[$params[1]][$params[2]] = $value;
		$this->writeFile('clist');
	}

	private function getClist($params, $default) {
		if(empty($this->clist))
			$this->readFile('clist');				
		if(isset($this->clist[$params[1]][$params[2]])){
			return $this->clist[$params[1]][$params[2]];
		}
		return $default;
	}
	
	private function deleteClist($params){
		if(empty($this->clist))
			$this->readFile('clist');
		unset($this->clist[$params[1]][$params[2]]);
		$this->writeFile('clist');
	}
		
	private function readFile($varname){
		$cacheFile = $this->cachepath.$varname.'.cache';
		if (file_exists($cacheFile)){ 
			$this->$varname = $this->cache_decode(file_get_contents($cacheFile));
		}
	}
	
	private function writeFile($varname){
		$cacheFile = $this->cachepath.$varname.'.cache';
		file_put_contents($cacheFile, $this->cache_encode($this->$varname));
	}	
}