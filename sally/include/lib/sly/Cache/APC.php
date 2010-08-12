<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Cache_APC extends sly_Cache_Abstract {
	public static function getMaxKeyLength() {
		return 200; // unbekannt -> Schätzwert
	}

	public static function hasLocking() {
		return false;
	}

	public static function isAvailable() {
		// Wir müssen auch prüfen, ob Werte gespeichert werden können (oder ob nur der Opcode-Cache aktiviert ist).
		return function_exists('apc_store') && apc_store('test', 1, 1);
	}

	protected function _getRaw($key) { return apc_fetch($key); }
	protected function _get($key)    { return apc_fetch($key); }

	protected function _setRaw($key, $value, $expiration) { return apc_store($key, $value, $expiration); }
	protected function _set($key, $value, $expiration)    { return apc_store($key, $value, $expiration); }

	protected function _delete($key) { return apc_delete($key);          }
	protected function _isset($key)  { return apc_fetch($key) !== false; }

	protected function _increment($key) {
		$value = apc_fetch($key);
		return apc_store($key, $value + 1) !== false;
	}
}
