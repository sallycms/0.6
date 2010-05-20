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
 * Ist noch ein wrapper für $REX wird irgendwann mal umgebaut
 * 
 * @author zozi@webvariants.de
 *
 */
class sly_Configuration implements ArrayAccess
{
	private $config;
	private $filename;
	
	private static $instances;

	private function __construct($filename)
	{
		global $SLY;
		
		$this->filename = $filename;
		$this->config   = new ArrayObject(self::load($filename));
	}
	
	public static function load($filename)
	{
		global $SLY;
		
		if (!file_exists($filename)) {
			throw new Exception('Konfigurationsdatei '.$filename.' konnte nicht gefunden werden.');
		}
		
		$cacheDir = $SLY['DYNFOLDER'].'/internal/sally/yaml-cache';
		if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
		if (!is_dir($cacheDir)) throw new Exception('Cache-Verzeichnis '.$cacheDir.' konnte nicht erzeugt werden.');
		
		$file      = realpath($filename);
		$mtime     = filemtime($file);
		$cacheFile = $cacheDir.'/'.substr(md5($file), 0, 10).'.php';
		
		if (!file_exists($cacheFile) || $mtime > filemtime($cacheFile)) {
			$config = sfYaml::load($file);
			file_put_contents($cacheFile, '<?php $config = '.var_export($config, true).';');
		}
		else {
			include $cacheFile;
		}
		
		return $config;
	}
	
	public static function clearCache()
	{
		global $SLY;
		$cacheDir = $SLY['DYNFOLDER'].'/internal/sally/yaml-cache';
		if (is_dir($cacheDir)) array_map('unlink', glob($cacheDir.'/*'));
	}

	/**
	 * @return sly_Configuration
	 */
	public static function getInstance($filename = null)
	{
		global $SLY;
		
		if (!is_string($filename)) {
			$filename = $SLY['INCLUDE_PATH'].'/config/sally.yaml';
		}
		
		if (!self::$instances[$filename]) self::$instances[$filename] = new self($filename);
		return self::$instances[$filename];
	}

	public function get($key)
	{
		if (empty($key)) {
			return $this->config->getArrayCopy();
		}
		
		if (strpos($key, '/') === false) {
			return $this->config[$key];
		}
		
		$path = array_filter(explode('/', $key));
		$res  = $this->config;
		
		foreach ($path as $step) {
			if (!array_key_exists($step, $res)) break;
			$res = $res[$step];
		}
		
		return $res;
	}

	public function has($key)
	{
		if (strpos($key, '/') === false) {
			return $this->config->offsetExists($key);
		}
		
		$path = array_filter(explode('/', $key));
		$res  = $this->config;
		
		foreach ($path as $step){
			if (!array_key_exists($step, $res)) return false;
			$res = $res[$step];
		}
		
		return !empty($res);
	}

	public function set($key, $value)
	{
		if (strpos($key, '/') === false) {
			$this->config[$key] = $value;
			return $value;
		}
		
		// Da wir Schreibvorgänge anstoßen werden, arbeiten wir hier explizit
		// mit Referenzen. Ja, Referenzen sind i.d.R. böse, deshalb werden sie auch
		// in get() und has() nicht benutzt. Copy-on-Write und so.
		
		$path = array_filter(explode('/', $key));
		$res  = &$this->config;
		
		foreach ($path as $step) {
			if (!array_key_exists($step, $res)) {
				$res[$step] = array();
			}
			
			$res = &$res[$step];
		}
		
		$res = $value;
		return $value;
	}
	
	public function appendFile($filename, $key = null)
	{
		$data = self::load($filename);
		$this->appendArray($data, $key);
	}
	
	public function appendArray($array, $key = null)
	{
		if ($key !== null) {
			$this->set($key, $array);
		}
		else {
			foreach ($array as $k => $v) $this->config[$k] = $v;
		}
	}
	
	public function offsetExists($index)       { return $this->config->offsetExists($index);       }
	public function offsetGet($index)          { return $this->config->offsetGet($index);          }
	public function offsetSet($index, $newval) { return $this->config->offsetSet($index, $newval); }
	public function offsetUnset($index)        { return $this->config->offsetUnset($index);        }
}
