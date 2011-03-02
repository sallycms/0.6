<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup cache
 */
class sly_Cache_Filesystem extends sly_Cache implements sly_Cache_IFlushable {
	protected $dataDir = '';

	public function __construct($dataDirectory) {
		self::create($dataDirectory);
		$this->dataDir = $dataDirectory;
	}

	public function lock($namespace, $key, $duration = 1) {
		$key = parent::getFullKeyHelper($namespace, $key);
		$dir = $this->dataDir.'/lock#'.$key;

		clearstatcache();
		return @mkdir($dir, 0777);
	}

	public function unlock($namespace, $key) {
		$key = parent::getFullKeyHelper($namespace, $key);
		$dir = $this->dataDir.'/lock#'.$key;

		clearstatcache();
		return is_dir($dir) ? rmdir($dir) : true;
	}

	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50) {
		$key            = parent::getFullKeyHelper($namespace, $key);
		$dir            = $this->dataDir.'/lock#'.$key;
		$start          = microtime(true);
		$waited         = 0;
		$checkInterval *= 1000;

		while ($waited < $maxWaitTime && is_dir($dir)) {
			usleep($checkInterval);
			$waited = microtime(true) - $start;
			clearstatcache();
		}

		if (!is_dir($dir)) {
			return $this->get($namespace, $key, $default);
		}
		else {
			return $default;
		}
	}

	public function set($namespace, $key, $value) {
		$filename = $this->getFilename($namespace, $key);
		$level    = error_reporting(0);

		touch($filename, 0777);
		file_put_contents($filename, serialize($value));

		error_reporting($level);
		return $value;
	}

	public function get($namespace, $key, $default = null) {
		$filename = $this->getFilename($namespace, $key);

		if (!file_exists($filename)) {
			return $default;
		}

		$data = file_get_contents($filename);
		return unserialize($data);
	}

	public function exists($namespace, $key) {
		return file_exists($this->getFilename($namespace, $key));
	}

	public function delete($namespace, $key) {
		return @unlink($this->getFilename($namespace, $key));
	}

	public function flush($namespace, $recursive = false) {
		// handle our own cache

		$namespace = self::getDirFromNamespace(self::cleanupNamespace($namespace));
		$root      = $this->dataDir.'/'.$namespace;

		// Wenn wir rekursiv löschen, können wir wirklich alles in diesem Verzeichnis
		// löschen.

		if ($recursive) {
			return self::deleteRecursive($root);
		}

		// Löschen wir nicht rekursiv, dürfen wir nur das data~-Verzeichnis
		// entfernen.

		else {
			return self::deleteRecursive($root.'/data~');
		}
	}

	protected static function createNamespaceDir($namespace, $root, $hash) {
		if (!empty($namespace)) {
			$thisPart = array_shift($namespace);
			$dir      = $root.'/'.$thisPart;

			self::create($dir);

			return self::createNamespaceDir($namespace, $dir, $hash);
		}
		else {
			// Zuletzt erzeugen wir das Verteilungsverzeichnis für die Cache-Daten,
			// damit nicht alle Dateien in einem großen Verzeichnis leben müssen.

			// Dazu legen wir in dem Zielnamespace ein Verzeichnis "data~" an,
			// in dem die 00, 01, ... 99 ... EF, FF-Verzeichnisse erzeugt werden.
			// Dadurch vermeiden wir Kollisionen mit Namespaces, die auch Teilnamespaces
			// der Länge 2 haben.

			$dir = $root.'/data~';
			self::create($dir);

			// Jetzt kommen die kleinen Verzeichnisse...

			$dir = $dir.'/'.$hash[0].$hash[1];
			self::create($dir);

			return true;
		}
	}

	private function dataDirExists($nsDir) {
		$dirname = $this->dataDir.'/'.$nsDir.'/data~';
		return is_dir($dirname);
	}

	protected function getFilename($namespace, $key) {
		$namespace = parent::cleanupNamespace($namespace);
		$key       = parent::cleanupKey($key);
		$hash      = md5($key);
		$nsDir     = self::getDirFromNamespace($namespace);

		if (!$this->dataDirExists($nsDir)) {
			self::createNamespaceDir(explode('.', $namespace), $dir, $hash);
		}

		// Finalen Dateipfad erstellen

		$dir = $this->dataDir.'/'.$nsDir.'/data~/'.$hash[0].$hash[1];
		self::create($dir);

		return $dir.'/'.$hash;
	}

	protected static function getSubNamespaces($namespace) {
		$namespace = self::getDirFromNamespace(parent::cleanupNamespace($namespace));
		$dir       = $this->dataDir.'/'.$namespace;
		$dataDir   = 'data~';

		// Verzeichnisse ermitteln

		$namespaces = is_dir($namespaces) ? scandir($namespaces) : array(); // Warning vermeiden (scandir gäbe auch false zurück)

		// data~-Verzeichnis entfernen, falls vorhanden

		$dataDirIndex = array_search($dataDir, $namespaces);

		if ($dataDirIndx !== false) {
			unset($namespaces[$dataDirIndex]);
		}

		sort($namespaces);
		return array_values($namespaces);
	}

	protected static function deleteRecursive($root) {
		if (!is_dir($root)) {
			return true;
		}

		try {
			$dirIterator = new RecursiveDirectoryIterator($root);
			$recIterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::CHILD_FIRST);
			$status      = true;
			$level       = error_reporting(0);

			foreach ($recIterator as $file) {
				if ($file->isDir()) $status &= rmdir($file);
				elseif ($file->isFile()) $status &= unlink($file);
			}

			rmdir($root);

			$recIterator = null;
			$dirIterator = null;

			error_reporting($level);
			clearstatcache();
			return $status;
		}
		catch (UnexpectedValueException $e) {
			return false;
		}
	}

	private static function create($dir) {
		if (!is_dir($dir)) {
			if (!@mkdir($dir, 0777, true)) {
				throw new sly_Cache_Exception(t('sly_cant_create_dir', $dir));
			}

			clearstatcache();
		}
	}
}
