<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Cache_Memcached extends sly_Cache_Abstract {
	protected $memcached = null;

	public static function getMaxKeyLength() {
		return 200; // unbekannt -> Schätzwert
	}

	public static function hasLocking() {
		return false;
	}

	public function __construct($host = 'localhost', $port = 11211) {
		global $I18N;

		$this->memcached = new Memcached();

		if (!$this->memcached->addServer($host, $port)) {
			throw new sly_Cache_Exception($I18N->msg('sly_cache_memcached_error', $host, $port));
		}
	}

	public static function isAvailable($host = 'localhost', $port = 11211) {
		if (!class_exists('Memcached')) {
			return false;
		}

		$testCache = new Memcached();

		if (!$testCache->addServer($host, $port)) {
			return false;
		}

		$available = $testCache->set('test', 1, 1);
		$testCache = null;

		return $available;
	}

	protected function _getRaw($key) { return $this->memcached->get($key); }
	protected function _get($key)    { return $this->memcached->get($key); }

	protected function _setRaw($key, $value, $expiration) { return $this->memcached->set($key, $value, $expiration); }
	protected function _set($key, $value, $expiration)    { return $this->memcached->set($key, $value, $expiration); }

	protected function _delete($key) {
		return $this->memcached->delete($key);
	}

	protected function _isset($key) {
		$this->memcached->get($path);
		return $this->memcached->getResultCode() != Memcached::RES_NOTFOUND;
	}

	protected function _increment($key) {
		return $this->memcached->increment($key) !== false;
	}
}
