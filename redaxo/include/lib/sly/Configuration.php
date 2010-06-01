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

class sly_Configuration implements ArrayAccess {
	
	const STORE_PROJECT       = 1;
	const STORE_LOCAL         = 2;
	const STORE_LOCAL_DEFAULT = 3;
	const STORE_STATIC        = 4;
	const STORE_TEMP          = 5;

	private $mode              = array();
	private $loadedConfigFiles = array();
	
	private $staticConfig;
	private $localConfig;
	private $projectConfig;
	private $tempConfig;
	
	private static $instance;

	private function __construct() {
		$this->staticConfig  = new sly_Util_Array();
		$this->localConfig   = new sly_Util_Array();
		$this->projectConfig = new sly_Util_Array();
		$this->tempConfig    = new sly_Util_Array();
	}
	
	protected function getCacheDir() {
		$dir = SLY_DYNFOLDER.DIRECTORY_SEPARATOR.'internal'.DIRECTORY_SEPARATOR.'sally'.DIRECTORY_SEPARATOR.'config';
		if (!is_dir($dir) && !mkdir($dir, '0755', true)) {
			throw new Exception('Cache-Verzeichnis '.$dir.' konnte nicht erzeugt werden.');
		}
		return $dir;
	}
	
	protected function getCacheFile($filename) {
		$dir = $this->getCacheDir();
		//$filename = str_replace('\\', '/', realpath($filename));
		return $dir.DIRECTORY_SEPARATOR.'config_'.str_replace(DIRECTORY_SEPARATOR, '_', $filename).'.cache.php';
	}
	
	protected function getLocalCacheFile() {
		return $this->getCacheDir().DIRECTORY_SEPARATOR.'sly_local.cache.php';
	}
	
	protected function isCacheValid($origfile, $cachefile) {
		return file_exists($cachefile) && filemtime($origfile) < filemtime($cachefile);
	}
	
	public function loadStatic($filename) {
		return $this->loadInternal($filename, self::STORE_STATIC);
	}
	
	public function loadLocalDefaults($filename, $force = false) {
		return $this->loadInternal($filename, self::STORE_LOCAL_DEFAULT, $force);
	}

	public function loadLocalConfig($force = false){
		$file = $this->getLocalCacheFile();
		if (file_exists($file)) {
			include $file;
			$this->localConfig = new sly_Util_Array($config);
		}
	}

	public function loadProjectConfig(){
		if (sly_Core::getPersistentRegistry()->has('sly_ProjectConfig')) {
			$this->projectConfig = sly_Core::getPersistentRegistry()->get('sly_ProjectConfig');
		}
	}
	
	protected function loadInternal($filename, $mode, $force = false) {
		if ($mode != self::STORE_LOCAL_DEFAULT && $mode != self::STORE_STATIC) {
			throw new Exception('Konfigurationsdateien können nur mit STORE_STATIC oder STORE_LOCAL_DEFAULT geladen werden.');
		}
		if (empty($filename) || !is_string($filename)) throw new Exception('Keine Konfigurationsdatei angegeben.');
		if (!file_exists($filename)) throw new Exception('Konfigurationsdatei '.$filename.' konnte nicht gefunden werden.');
		
		$isStatic = $mode == self::STORE_STATIC;

		// force gibt es nur bei STORE_LOCAL_DEFAULT
		$force = $force && !$isStatic;
		
		$cachefile = $this->getCacheFile($filename);
		
		// prüfen ob konfiguration in diesem request bereits geladen wurde
		if (!$force && isset($this->loadedConfigFiles[$filename])) {
			// statisch geladene konfigurationsdaten werden innerhalb des requests nicht mehr überschrieben 
			if ($isStatic && file_exists($cachefile) && filemtime($filename) > filemtime($cachefile)) {
				trigger_error('Statische Konfigurationsdatei '.$filename.' wurde bereits in einer anderen Version geladen! Daten wurden nicht überschrieben.', E_USER_WARNING);
			}
			return false;
		}
		
		$config = array();
		
		// konfiguration aus cache holen, wenn cache aktuell
		if ($this->isCacheValid($filename, $cachefile)) include $cachefile;
		// konfiguration aus yaml laden
		else $config = $this->loadYaml($filename, $cachefile);

		// geladene konfiguration in globale konfiguration mergen
		$this->setInternal('/', $config, $mode, $force);
		
		$this->loadedConfigFiles[$filename] = true;
		
		return $config;
	}
	
	protected function loadYaml($filename, $cachefile) {
		if (!file_exists($filename)) throw new Exception('Konfigurationsdatei '.$filename.' konnte nicht gefunden werden.');
		$config = sfYaml::load($filename);
		file_put_contents($cachefile, '<?php $config = '.var_export($config, true).';');
		return $config;
	}
	
	/**
	 * @return sly_Configuration
	 */
	public static function getInstance() {
		if (!self::$instance) self::$instance = new self();

		return self::$instance;
	}

	public function get($key) {
		$s = (empty($key) || $this->staticConfig->has($key))  ? $this->staticConfig->get($key)  : array();
		$l = (empty($key) || $this->localConfig->has($key))   ? $this->localConfig->get($key)   : array();
		$p = (empty($key) || $this->projectConfig->has($key)) ? $this->projectConfig->get($key) : array();
		$t = (empty($key) || $this->tempConfig->has($key))    ? $this->tempConfig->get($key)    : array();
		if (!is_array($t)) return $t;
		if (!is_array($s)) return $s;
		if (!is_array($l)) return $l;
		if (!is_array($p)) return $p;
		return array_replace_recursive($t, $s, $l, $p);
	}

	public function has($key) {
		return $this->staticConfig->has($key) || $this->localConfig->has($key) || $this->projectConfig->has($key);
	}
	
	protected function setStatic($key, $value) {
		return $this->setInternal($key, $value, self::STORE_STATIC);
	}

	public function setLocal($key, $value) {
		return $this->setInternal($key, $value, self::STORE_LOCAL);
	}

	public function setLocalDefault($key, $value, $force = false) {
		return $this->setInternal($key, $value, self::STORE_LOCAL, $force);
	}
	
	public function set($key, $value, $mode = self::STORE_PROJECT) {
		return $this->setInternal($key, $value, $mode);
	}
	
	protected function setInternal($key, $value, $mode, $force = false) {
		if (is_null($key) || strlen($key) === 0) throw new Exception('Key '.$key.' ist nicht erlaubt!');
       	if (is_array($value) && !empty($value)){
			foreach ($value as $ikey => $val) {
				$currentPath = trim($key.'/'.$ikey, '/');
				if (is_array($val) && !empty($val)) $this->setInternal($currentPath, $val, $mode, $force);
				else $this->setInternal($currentPath, $val, $mode, $force);
			}

			return $value;
		}
		
		if (empty($mode)) $mode = self::STORE_PROJECT;
		
		if ($mode == self::STORE_TEMP) {
			 if ($this->getMode($key) != self::STORE_TEMP) {
			 	return $this->setInternal($key, $value, $this->getMode($key));
			 }
			 else {
			 	return $this->tempConfig->set($key, $value);
			 }
		}
		
		$this->setMode($key, $mode);
		
		
		if ($mode == self::STORE_STATIC) {
			return $this->staticConfig->set($key, $value);
		}
		
		if ($mode == self::STORE_LOCAL) {
			return $this->localConfig->set($key, $value);
		}
		
		if ($mode == self::STORE_LOCAL_DEFAULT) {
			if ($force || !$this->localConfig->has($key)) {
				return $this->localConfig->set($key, $value);
			}
			return false;
		}
		
		// case: sly_Configuration::STORE_PROJECT
		return $this->projectConfig->set($key, $value);
		
	}
	
	protected function setMode($key, $mode) {
		if ($mode == self::STORE_LOCAL_DEFAULT) $mode = self::STORE_LOCAL;
		if ($this->checkMode($key, $mode)) return;
		if (isset($this->mode[$key]) && $this->mode[$key] != self::STORE_TEMP) {
			throw new Exception('Mode für '.$key.' wurde bereits auf '.$this->mode[$key].' gesetzt.');
		}
		$this->mode[$key] = $mode;
	}

	protected function getMode($key) {
		if (!isset($this->mode[$key])) return null;
		return $this->mode[$key];
	}
	
	protected function checkMode($key, $mode) {
		if ($mode == self::STORE_LOCAL_DEFAULT) $mode = self::STORE_LOCAL;
		return !isset($this->mode[$key]) || $this->mode[$key] == $mode;
	}

	protected function flush() {
		file_put_contents($this->getLocalCacheFile(), '<?php $config = '.var_export($this->localConfig->get(null), true).';');
		sly_Core::getPersistentRegistry()->set('sly_ProjectConfig', $this->projectConfig);
	}
	
	public function __destruct() {
		$this->flush();
	}
	
	public function offsetExists($index)       { return $this->has($index); }
	public function offsetGet($index)          { return $this->get($index); }
	public function offsetSet($index, $newval) {
		var_dump($index);
		if (strpos($index, '/') !== false) trigger_error('Slashes können in Keys auf $REX nicht benutzt werden. ('.$index.')', E_USER_ERROR);
		
		//if (is_array($newval)) {
		//	$this->offsetSetRecursive($index, $newval);
		//	return $newval;
		//}
		//else
		return $this->set($index, $newval, self::STORE_TEMP);
	}
	
	private function offsetSetRecursive($key, $value, $path = '') {
		if (is_array($value)) {
			foreach ($value as $k2 => $v2) {
				$this->offsetSetRecursive($k2, $v2, $key);
			}
		}
		else $this->set($path.'/'.$key, $value, self::STORE_TEMP);
	}
	public function offsetUnset($index)        { }
}
