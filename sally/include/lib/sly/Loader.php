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
 * @ingroup core
 */
class sly_Loader {
	protected static $loadPaths       = array();
	protected static $counter         = 0;
	protected static $pathHash        = 0;
	protected static $pathCache       = array();
	protected static $enablePathCache = false;

	public static function enablePathCache($flag = true) {
		self::$enablePathCache = (boolean) $flag;
		self::$pathHash        = $flag ? md5(json_encode(self::$loadPaths)) : 0;
	}

	public static function addLoadPath($path, $hiddenPrefix = '') {
		$path = realpath($path);

		if ($path) {
			$path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			self::$loadPaths[$path] = $hiddenPrefix;

			if (self::$enablePathCache) {
				self::$pathHash = md5(json_encode(self::$loadPaths));

				// import path cache

				$dir      = SLY_DYNFOLDER.'/internal/sally/loader';
				$filename = $dir.'/'.self::$pathHash.'.php';

				if (!is_dir($dir)) {
					@mkdir($dir, 0777);
				}

				if (file_exists($filename)) {
					include $filename;
					self::$pathCache = array_merge(self::$pathCache, $config);
				}
			}
		}
	}

	public static function register() {
		if (function_exists('spl_autoload_register')) {
			spl_autoload_register(array('sly_Loader', 'loadClass'));
		}
		else {
			function __autoload($className) {
				self::loadClass($className);
			}
		}
	}

	public static function loadClass($className) {
		global $REX; // für Code, der direkt beim Include ausgeführt wird.

		if (class_exists($className, false)) {
			return true;
		}

		$found = false;
		$upper = strtoupper($className);

		if (isset(self::$pathCache[$className])) {
			$fullPath = self::$pathCache[$className];
			$found    = true;
		}
		else {
			foreach (self::$loadPaths as $path => $prefix) {
				// Präfix vom Klassennamen abschneiden, wenn Klasse damit beginnt.

				if (!empty($prefix) && strpos($upper, strtoupper($prefix)) === 0) {
					$shortClass = substr($className, strlen($prefix));
				}
				else {
					$shortClass = $className;
				}

				$file     = str_replace('_', DIRECTORY_SEPARATOR, $shortClass).'.php';
				$fullPath = $path.DIRECTORY_SEPARATOR.$file;

				// file_exists + !is_dir is faster than calling is_file and since we
				// do not care whether the file really has a class in it, we can skip
				// this check.

				if (file_exists($fullPath) && !is_dir($fullPath)) {
					$found = true;

					if (self::$enablePathCache) {
						// update path cache file
						self::$pathCache[$className] = realpath($fullPath);
						$filename = SLY_DYNFOLDER.'/internal/sally/loader/'.self::$pathHash.'.php';
						file_put_contents($filename, '<?php $config = '.var_export(self::$pathCache, true).';');
					}

					break;
				}
			}
		}

		if ($found) {
			include_once $fullPath;

			// init classes

			switch ($className) {
				case 'sly_Log':
					sly_Log::setLogDirectory(SLY_DYNFOLDER.'/internal/sally/logs');
					break;
			}

			++self::$counter;
			return $fullPath;
		}

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notifyUntil('__AUTOLOAD', $className);

		if (class_exists($className, false)) {
			++self::$counter;
			return true;
		}

		return false;
	}

	public static function getClassCount() {
		return self::$counter;
	}
}
