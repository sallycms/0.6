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

	public function exists() {
		return is_dir($this->directory);
	}

	public function listPlain($files = true, $directories = true, $dotFiles = false, $absolute = false, $sortFunction = 'natsort') {
		if (!$files && !$directories) return array();
		if (!is_dir($this->directory)) return false;

		if (!empty($sortFunction) && !function_exists($sortFunction)) {
			throw new sly_Exception('Sort function '.$sortFunction.' does not exist!');
		}

		$handle = opendir($this->directory);
		$list   = array();

		while ($file = readdir($handle)) {
			if ($file == '.' || $file == '..') continue;
			if ($file[0] == '.' && !$dotFiles) continue;

			$abs = self::join($this->directory, $file);

			if (is_dir($abs)) {
				if ($directories) $list[] = $absolute ? $abs : $file;
			}
			else {
				$list[] = $absolute ? $abs : $file;
			}
		}

		closedir($handle);
		if (!empty($sortFunction)) $sortFunction($list);

		return $list;
	}

	public function listRecursive($dotFiles = false, $absolute = false) {
		if (!is_dir($this->directory)) return false;

		$iterator = new RecursiveDirectoryIterator($this->directory);
		$iterator = new RecursiveIteratorIterator($iterator);
		$list     = array();
		$base     = rtrim(realpath($this->directory), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

		foreach ($iterator as $filename => $fileInfo) {
			if ($dotFiles && $absolute) {
				$list[] = $filename;
				continue;
			}

			$relative = substr($filename, strlen($base));

			if (!$dotFiles) {
				$parts     = explode(DIRECTORY_SEPARATOR, $relative);
				$isDotPath = false;

				foreach ($parts as $path) {
					if ($path[0] == '.') {
						$isDotPath = true;
						break;
					}
				}

				if ($isDotPath) {
					continue;
				}
			}

			$list[] = $absolute ? $filename : $relative;
		}

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
			$path = trim(self::normalize($path), DIRECTORY_SEPARATOR);
		}

		return ($isAbs ? DIRECTORY_SEPARATOR : '').implode(DIRECTORY_SEPARATOR, $paths);
	}

	public static function normalize($path) {
		$s     = DIRECTORY_SEPARATOR;
		$isAbs = $path[0] == '/' || $path[0] == '\\';
		$path  = str_replace(array('/', '\\'), $s, $path);
		$path  = implode($s, array_filter(explode($s, $path)));

		return ($isAbs ? $s : '').$path;
	}
}
