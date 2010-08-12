<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Configuration {

	const STORE_PROJECT         = 1;
	const STORE_LOCAL           = 2;
	const STORE_LOCAL_DEFAULT   = 3;
	const STORE_STATIC          = 4;
	const STORE_PROJECT_DEFAULT = 5;

	private $mode              = array();
	private $loadedConfigFiles = array();

	private $staticConfig;
	private $localConfig;
	private $projectConfig;

	private $localConfigModified = false;
	private $projectConfigModified = false;

	private static $instance;

	private function __construct() {
		$this->staticConfig  = new sly_Util_Array();
		$this->localConfig   = new sly_Util_Array();
		$this->projectConfig = new sly_Util_Array();
	}

	protected function getCacheDir() {
		$dir = SLY_DYNFOLDER.DIRECTORY_SEPARATOR.'internal'.DIRECTORY_SEPARATOR.'sally'.DIRECTORY_SEPARATOR.'config';

		if (!sly_Util_Directory::create($dir)) {
			throw new sly_Exception('Cache-Verzeichnis '.$dir.' konnte nicht erzeugt werden.');
		}

		return $dir;
	}

	protected function getCacheFile($filename) {
		$dir      = $this->getCacheDir();
		$filename = realpath($filename);

		// Es kann sein, dass AddOns über Symlinks eingebunden werden. In diesem
		// Fall liegt das Verzeichnis ggf. ausßerhalb von SLY_BASE und kann dann
		// nicht so behandelt werden wie ein "lokales" AddOn.

		if (sly_Util_String::startsWith($filename, SLY_BASE)) {
			$filename = substr($filename, strlen(SLY_BASE) + 1);
		}
		else {
			// Laufwerk:/.../ korrigieren
			$filename = str_replace(':', '', $filename);
		}

		return $dir.DIRECTORY_SEPARATOR.str_replace(DIRECTORY_SEPARATOR, '_', $filename).'.php';
	}

	protected function getLocalCacheFile() {
		return $this->getCacheDir().DIRECTORY_SEPARATOR.'sly_local.php';
	}

	//FIXME: protected machen wenn Konfigurationsumbau fertig
	public function getProjectCacheFile() {
		return $this->getCacheDir().DIRECTORY_SEPARATOR.'sly_project.php';
	}

	protected function isCacheValid($origfile, $cachefile) {
		return file_exists($cachefile) && filemtime($origfile) < filemtime($cachefile);
	}

	public function loadStatic($filename, $key = '/') {
		return $this->loadInternal($filename, self::STORE_STATIC, false, $key);
	}

	public function loadLocalDefaults($filename, $force = false, $key = '/') {
		return $this->loadInternal($filename, self::STORE_LOCAL_DEFAULT, $force, $key);
	}

	public function loadProjectDefaults($filename, $force = false, $key = '/') {
		return $this->loadInternal($filename, self::STORE_PROJECT_DEFAULT, $force, $key);
	}

	public function loadLocalConfig(){
		$file = $this->getLocalCacheFile();
		if (file_exists($file)) {
			include $file;
			$this->localConfig = new sly_Util_Array($config);
		}
	}

	public function loadProjectConfig(){
		$file = $this->getProjectCacheFile();
		if (file_exists($file)) {
			include $file;
			$this->projectConfig = new sly_Util_Array($config);
			//FIXME: else zweig weghauen wenn Konfigurationsumbau fertig
		} else {
			if (sly_Core::getPersistentRegistry()->has('sly_ProjectConfig')) {
				$this->projectConfig = sly_Core::getPersistentRegistry()->get('sly_ProjectConfig');
			}
		}
	}

	protected function loadInternal($filename, $mode, $force = false, $key = '/') {
		if ($mode != self::STORE_LOCAL_DEFAULT && $mode != self::STORE_STATIC && $mode != self::STORE_PROJECT_DEFAULT) {
			throw new Exception('Konfigurationsdateien können nur mit STORE_STATIC, STORE_LOCAL_DEFAULT oder STORE_PROJECT_DEFAULT geladen werden.');
		}
		if (empty($filename) || !is_string($filename)) throw new Exception('Keine Konfigurationsdatei angegeben.');
		if (!file_exists($filename)) throw new Exception('Konfigurationsdatei '.$filename.' konnte nicht gefunden werden.');

		$isStatic = $mode == self::STORE_STATIC;

		// force gibt es nur bei STORE_*_DEFAULT
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
		$this->setInternal($key, $config, $mode, $force);

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

	public function get($key, $default = null) {
		if (!$this->has($key)) return $default;

		$s = (empty($key) || $this->staticConfig->has($key))  ? $this->staticConfig->get($key)  : array();
		$l = (empty($key) || $this->localConfig->has($key))   ? $this->localConfig->get($key)   : array();
		$p = (empty($key) || $this->projectConfig->has($key)) ? $this->projectConfig->get($key) : array();

		if (!is_array($p)) return $p;
		if (!is_array($l)) return $l;
		if (!is_array($s)) return $s;

		return array_replace_recursive($s, $l, $p);
	}

	public function has($key) {
		return $this->staticConfig->has($key) || $this->localConfig->has($key) || $this->projectConfig->has($key);
	}

	public function remove($key){
		$this->localConfig->remove($key);
		$this->projectConfig->remove($key);
	}

	public function setStatic($key, $value) {
		return $this->setInternal($key, $value, self::STORE_STATIC);
	}

	public function setLocal($key, $value) {
		return $this->setInternal($key, $value, self::STORE_LOCAL);
	}

	public function setLocalDefault($key, $value, $force = false) {
		return $this->setInternal($key, $value, self::STORE_LOCAL_DEFAULT, $force);
	}

	public function setProjectDefault($key, $value, $force = false) {
		return $this->setInternal($key, $value, self::STORE_PROJECT_DEFAULT, $force);
	}

	public function set($key, $value, $mode = self::STORE_PROJECT) {
		return $this->setInternal($key, $value, $mode);
	}

	protected function setInternal($key, $value, $mode, $force = false) {
		if (is_null($key) || strlen($key) === 0) {
			throw new sly_Exception('Key '.$key.' ist nicht erlaubt!');
		}
		if (is_array($value) && !empty($value)) {
			foreach ($value as $ikey => $val) {
				$currentPath = trim($key.'/'.$ikey, '/');
				if (is_array($val) && !empty($val)) $this->setInternal($currentPath, $val, $mode, $force);
				else $this->setInternal($currentPath, $val, $mode, $force);
			}

			return $value;
		}

		if (empty($mode)) $mode = self::STORE_PROJECT;

		$this->setMode($key, $mode);

		if ($mode == self::STORE_STATIC) {
			return $this->staticConfig->set($key, $value);
		}

		if ($mode == self::STORE_LOCAL) {
			$this->localConfigModified = true;
			return $this->localConfig->set($key, $value);
		}

		if ($mode == self::STORE_LOCAL_DEFAULT) {
			if ($force || !$this->localConfig->has($key)) {
				return $this->localConfig->set($key, $value);
			}
			return false;
		}

		if ($mode == self::STORE_PROJECT_DEFAULT) {
			if ($force || !$this->projectConfig->has($key)) {
				return $this->projectConfig->set($key, $value);
			}
			return false;
		}

		// case: sly_Configuration::STORE_PROJECT

		$this->projectConfigModified = true;
		return $this->projectConfig->set($key, $value);
	}

	protected function setMode($key, $mode) {
		if ($mode == self::STORE_LOCAL_DEFAULT) $mode = self::STORE_LOCAL;
		if ($this->checkMode($key, $mode)) return;
		if (isset($this->mode[$key])) {
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
		if($this->localConfigModified) {
			file_put_contents($this->getLocalCacheFile(), '<?php $config = '.var_export($this->localConfig->get(null), true).';');
		}

		if($this->projectConfigModified) {
			file_put_contents($this->getProjectCacheFile(), '<?php $config = '.var_export($this->projectConfig->get(null), true).';');
			//FIXME: db update weghauen wenn Konfigurationsumbau fertig
			try {
				sly_Core::getPersistentRegistry()->set('sly_ProjectConfig', $this->projectConfig);
			}
			catch (Exception $e) {
				// Could not save project configuration. This is only "ok" while we're
				// in setup mode and don't know the correct database name yet.

				if (!sly_Core::config()->get('SETUP')) {
					trigger_error('Could not save project configuration on script exit.', E_USER_WARNING);
				}
			}
		}
	}

	public function __destruct() {
		$this->flush();
	}
}
