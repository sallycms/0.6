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

class sly_Util_Directory {
	protected $directory;
	
	public function __construct($directory, $createIfNeeded = false) {
		global $REX;
		
		$directory = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $directory);
		$directory = rtrim($directory, DIRECTORY_SEPARATOR);
		
		if (!is_dir($directory) && $createIfNeeded) {
			mkdir($directory, $REX['DIRPERM'], true);
		}
		
		$this->directory = $directory;
	}
	
	public function listPlain($files = true, $directories = true, $dotFiles = false, $absolute = false, $sortFunction = 'natsort') {
		if (!$files && !$directories) return array();
		if (!is_dir($this->directory)) return array();
		
		if (!empty($sortFunction) && !function_exists($sortFunction)) {
			throw new sly_Exception('Sort function '.$sortFunction.' does not exist!');
		}
		
		$handle = opendir($this->directory);
		$list   = array();
		
		while ($file = readdir($handle)) {
			if ($file == '.' || $file == '..') continue;
			
			$abs = self::join($this->directory, $file);
			
			if (is_dir($abs)) {
				if ($directories) $list[] = $absolute ? $abs : $file;
			}
			else {
				if ($file[0] == '.' && !$dotFiles) continue;
				$list[] = $absolute ? $abs : $file;
			}
		}
		
		closedir($handle);
		$sortFunction($list);
		
		return $list;
	}
	
	public function __toString() {
		$dir = realpath($this->directory);
		return $dir ? $dir : $this->directory.' (not existing)';
	}
	
	public static function join($paths) {
		$paths = func_get_args();
		$isAbs = $paths[0][0] == '/' || $paths[0][0] == '\\';
		
		foreach ($paths as &$path) {
			$path = trim($path, '/\\');
		}
		
		return ($isAbs ? '/' : '').implode('/', $paths);
	}
}
