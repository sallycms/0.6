<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup util
 */
class sly_Util_Directory {
	protected $directory;

	public function __construct($directory, $createIfNeeded = false) {
		$directory = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $directory);
		$directory = rtrim($directory, DIRECTORY_SEPARATOR);

		if (!is_dir($directory) && $createIfNeeded) {
			self::create($directory);
		}

		$this->directory = $directory;
	}

	public static function create($path, $perm = 0777) {
		$path = self::normalize($path);

		if (!is_dir($path)) {
			if (!mkdir($path, $perm, true)) {
				return false;
			}

			// chmod all path components on their own!
			// FIXME: do not chmod previously existing folders, concept of this
			// function is not that good

			$base = '';
			//strip SLY_BASE from path
			if(startsWith($path, SLY_BASE)) {
				$base = SLY_BASE;
				$path = substr($path, strlen(SLY_BASE));
			}
			foreach (explode(DIRECTORY_SEPARATOR, $path) as $component) {
				chmod($base.$component, $perm);
				$base .= $component.'/';
			}
		}

		return true;
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
				if ($files) $list[] = $absolute ? $abs : $file;
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

		foreach ($paths as $idx => &$path) {
			if ($path === null || $path === false || $path === '') {
				unset($paths[$idx]);
				continue;
			}

			$path = trim(self::normalize($path), DIRECTORY_SEPARATOR);
		}

		return ($isAbs ? DIRECTORY_SEPARATOR : '').implode(DIRECTORY_SEPARATOR, $paths);
	}

	public static function normalize($path) {
		static $s = DIRECTORY_SEPARATOR;
		static $p = null;

		if ($p === null) {
			$p = '#'.preg_quote($s, '#').'+#';
		}

		$path  = str_replace(array('\\', '/'), $s, $path);
		$path  = rtrim($path, $s);

		if (strpos($path, $s.$s) === false) {
			return $path;
		}

		$parts = preg_split($p, $path);
		return implode($s, $parts);
	}

	public static function getRelative($path, $base = null) {
		if ($base === null) $base = SLY_BASE;
		$path = self::normalize(realpath($path));
		$base = self::normalize(realpath($base));

		if (!sly_Util_String::startsWith($path, $base)) {
			return $path;
		}

		return substr($path, strlen($base) +1);
	}

	public static function createHttpProtected($path) {
		$status = self::create($path);
		if ($status && !file_exists($path.'/.htaccess')) {
			$htaccess = "order deny,allow\ndeny from all";
			$status   = @file_put_contents($path.'/.htaccess', $htaccess) > 0;
		}
		return $status;
	}
}