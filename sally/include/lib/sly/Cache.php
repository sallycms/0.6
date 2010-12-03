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
 * @ingroup cache
 */
abstract class sly_Cache {
	protected $expiration = 0; ///< int  niemals ablaufen (nur, um Platz im Server zu schaffen)

	private static $instances       = null;   ///< array
	private static $cachingStrategy = null;   ///< string
	private static $cacheDisabled   = false;  ///< boolean

	private static $cacheImpls = array(
		'sly_Cache_APC'          => 'APC',
		'sly_Cache_Blackhole'    => 'Blackhole',
		'sly_Cache_Filesystem'   => 'Filesystem',
		'sly_Cache_eAccelerator' => 'eAccelerator',
		'sly_Cache_Memcache'     => 'Memcache',
		'sly_Cache_Memcached'    => 'Memcached',
		'sly_Cache_Memory'       => 'Memory',
		'sly_Cache_XCache'       => 'XCache',
		'sly_Cache_ZendServer'   => 'ZendServer'
	);

	/**
	 * @param string $className
	 * @param string $name
	 */
	public static function addCacheImpl($className, $name) {
		self::$cacheImpls[$className] = $name;
	}

	/**
	 * @return array  [className: title, className: title]
	 */
	public static function getAvailableCacheImpls() {
		$result = array();
		foreach (self::$cacheImpls as $cacheimpl => $name) {
			$available = call_user_func(array($cacheimpl, 'isAvailable'));
			if ($available) $result[$cacheimpl] = $name;
		}
		return $result;
	}

	/**
	 * @return boolean  always true
	 */
	public static function isAvailable() {
		return true;
	}

	public static function disableCaching() {
		self::$cacheDisabled = true;
	}

	public static function enableCaching() {
		self::$cacheDisabled = false;
	}

	/**
	 * @return string
	 */
	public static function getStrategy() {
		return sly_Core::config()->get('CACHING_STRATEGY', 'sly_Cache_Memory');
	}

	/**
	 * @return string
	 */
	public static function getFallbackStrategy() {
		return sly_Core::config()->get('FALLBACK_CACHING_STRATEGY', 'sly_Cache_Blackhole');
	}

	/**
	 * @param  string $forceCache
	 * @return sly_ICache
	 */
	public static function factory($forceCache = null) {
		if (self::$cacheDisabled && $forceCache != 'sly_Cache_Blackhole') {
			return self::factory('sly_Cache_Blackhole');
		}

		if (self::$cachingStrategy === null) {
			self::$cachingStrategy = self::getStrategy();
		}

		if ($forceCache !== null) {
			$cachingStrategy = $forceCache;
		}
		else {
			$cachingStrategy = self::$cachingStrategy;
		}

		if (!empty(self::$instances[$cachingStrategy])) {
			return self::$instances[$cachingStrategy];
		}

		// Versuchen, den gewählten Cache in der Datei zu finden

		if ($forceCache === null) {
			$cachingStrategy = self::getStrategy();
		}
		else {
			$cachingStrategy = $forceCache;
		}

		// Prüfen, ob der Cache verfügbar ist

		$available = call_user_func(array($cachingStrategy, 'isAvailable'));

		if (!$available) {
			$fallback = self::getFallbackStrategy();
			// Warnung auslösen, um jemanden auf das Problem aufmerksam zu machen
			trigger_error('Bad caching strategy. Falling back to '.$fallback, E_USER_WARNING);
			$cachingStrategy = $fallback;
		}

		// Wir merken uns den aktuell gewählten Cache.

		if ($forceCache === null) {
			self::$cachingStrategy = $cachingStrategy;
		}

		$config      = sly_Core::config();
		$instname    = $config->get('INSTNAME');
		$tablePrefix = $config->get('DATABASE/TABLE_PREFIX');

		switch ($cachingStrategy) {
			case 'sly_Cache_APC':
			case 'sly_Cache_eAccelerator':
			case 'sly_Cache_XCache':
			case 'sly_Cache_ZendServer':

				$cache = new $cachingStrategy();
				$cache->setNamespacePrefix($instname);
				$cache->setExpiration(0);
				break;

			case 'sly_Cache_Memcache':
			case 'sly_Cache_Memcached':

				$cache = new $cachingStrategy('localhost', 11211);
				$cache->setNamespacePrefix($instname);
				$cache->setExpiration(0);
				break;

			case 'sly_Cache_Filesystem':

				$path  = sly_Util_Directory::join(SLY_DYNFOLDER, 'internal', 'sally', 'fscache');
				$cache = new $cachingStrategy($path);
				break;

			default:
				$cache = new $cachingStrategy();
		}

		self::$instances[$cachingStrategy] = $cache;
		return $cache;
	}

	/**
	 * @throws sly_Cache_Exception
	 * @param  string $namespace
	 */
	protected static function cleanupNamespace($namespace) {
		$namespace = trim($namespace); // normale Whitespaces entfernen
		$namespace = preg_replace('#[^a-z0-9_\.-]#i', '_', $namespace);
		$namespace = preg_replace('#\.{2,}#', '.', $namespace);
		$namespace = trim($namespace, '.'); // führende und abschließende Punkte entfernen

		if (strlen($namespace) == 0) {
			throw new sly_Cache_Exception('An empty namespace was given.');
		}

		return strtolower($namespace);
	}

	/**
	 * @throws sly_Cache_Exception
	 * @param  string $key
	 */
	protected static function cleanupKey($key) {
		$key = trim($key); // normale Whitespaces entfernen
		$key = preg_replace('#[^a-z0-9_\.-]#i', '_', $key);
		$key = preg_replace('#\.{2,}#', '.', $key);
		$key = trim($key, '.'); // führende und abschließende Punkte entfernen

		if (strlen($key) == 0) {
			throw new sly_Cache_Exception('An empty key was given.');
		}

		return strtolower($key);
	}

	/**
	 * @param string $namespace
	 */
	protected static function getDirFromNamespace($namespace) {
		return str_replace('.', DIRECTORY_SEPARATOR, $namespace);
	}

	/**
	 * @param string $namespace
	 * @param string $newSep
	 */
	protected static function replaceSeparator($namespace, $newSep) {
		return str_replace('.', $newSep, $namespace);
	}

	/**
	 * @param string $args  Call this method with as many arguments as you want.
	 */
	protected static function concatPath($args) {
		$args = func_get_args();
		return implode(DIRECTORY_SEPARATOR, $args);
	}

	/**
	 * Diese Methode sagt den einzelnen Caches, welches Zeichen weder in
	 * Namespacenamen noch in Keys vorkommen darf. Damit können die
	 * Implementierungen dieses Zeichen dann verwenden, um interne Strukturen
	 * zu kennzeichnen.
	 *
	 * @return string
	 */
	protected static function getSafeDirChar() {
		return '~';
	}

	/**
	 * @param string $prefix
	 */
	public function setNamespacePrefix($prefix) {
		$this->namespacePrefix = self::cleanupNamespace($prefix);
	}

	/**
	 * @param int $expiration
	 */
	public function setExpiration($expiration) {
		$this->expiration = abs((int) $expiration);
	}

	/**
	 * @throws sly_Cache_Exception
	 * @param  string $key
	 * @param  int    $length
	 */
	protected static function checkKeyLength($key, $length) {
		if (strlen($key) > $length) {
			throw new sly_Cache_Exception('The given key is too long. At most '.$length.' characters are allowed.');
		}
	}

	/**
	 * @param  string $namespace
	 * @param  string $key
	 * @return string
	 */
	protected function getFullKeyHelper($namespace, $key) {
		$fullKey = self::cleanupNamespace($namespace);

		if (strlen($key) > 0) {
			$fullKey .= '$'.self::cleanupKey($key);
		}

		return $fullKey;
	}

	/**
	 * @param  string  $path
	 * @param  string  $keyName
	 * @param  boolean $excludeLastVersion
	 * @return string
	 */
	protected static function versionPathHelper($path, $keyName, $excludeLastVersion = false) {
		if ($excludeLastVersion) {
			$lastNode = array_pop($path);
			$lastNode = explode('@', $lastNode, 2);
			$lastNode = reset($lastNode);

			$path[] = $lastNode;
		}

		$path = implode('.', $path);

		if (!empty($keyName)) {
			$path .= '$'.$keyName;
		}

		return $path;
	}

	/**
	 * Cachekey erzeugen
	 *
	 * Diese Hilfsmethode kann genutzt werden, um in Abhängigkeit von beliebigen
	 * Parametern einen eindeutigen Key zu erzeugen. Dazu kann diese Methode
	 * mit beliebig vielen Parametern aufgerufen werden, die in Abhängigkeit von
	 * ihrem Typ verarbeitet und zu einem Key zusammengeführt werden.
	 *
	 * Ein Aufruf könnte beispielsweise wie folgt aussehen:
	 *
	 * @verbatim
	 * $myVar = 5;
	 * $key   = VarisaleCache::generateKey(12, $myVar, 'foobar', true, 4.45, array(1,2), 'x');
	 * $key   = '12_5_foobar_1_4#45_a[1_2]_x';
	 * @endverbatim
	 *
	 * @param  mixed $vars  Pseudo-Parameter. Diese Methode kann mit beliebig vielen Parametern aufgerufen werden
	 * @return string       der Objekt-Schlüssel
	 */
	public static function generateKey($vars) {
		$vars = func_get_args();
		$key  = array();

		foreach ($vars as $var) {
			switch (strtolower(gettype($var))) {
				case 'integer':
					$key[] = 'i'.$var;
					break;

				case 'string':

					if (preg_match('#[^a-z0-9-_]#i', $var)) {
						// Das Prozentzeichen kennzeichnet, dass es sich bei "2147483647"
						// um einen Hashwert (und nicht eine einfache Zahl) handelt.
						$var = '%'.substr(md5($var), 0, 8);
					}

					$key[] = 's'.strtolower($var);
					break;

				case 'boolean':
					$key[] = 'b'.((int) $var);
					break;

				case 'float':
				case 'double':
					$key[] = 'f'.$var;
					break;

				case 'object':
					$key[] = 'o'.substr(md5(print_r($var, true)), 0, 8);
					break;

				case 'resource':
					$key[] = 'r'.preg_replace('#[^a-z0-9_]#i', '_', get_resource_type($var));
					break;

				case 'array':
					$key[] = empty($val) ? 'a[]' : 'a['.call_user_func_array(array(__CLASS__, 'generateKey'), $var).']';
					break;

				case 'null':
					$key[] = 'n';
					break;
			}
		}

		return implode('_', $key);
	}
}
