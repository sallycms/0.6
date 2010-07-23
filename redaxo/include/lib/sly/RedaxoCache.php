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
 * Dies ist eine Platzhalterimplementierung für sly_ICache
 * Sie ist stabil.
 */
class sly_RedaxoCache implements sly_ICache {

	private $cache;
	private $persistence;

    public function __construct() {
		$this->flush();
    }

    public function setPersistence(sly_ICache $cache){
    	$this->persistence = $cache;
    }

    public function set($namespace, $key, $value) {
		$this->cache[$namespace][$key] = $value;
		if($this->persistence) $this->persistence->set($namespace, $key, $value);
    }

    public function get($namespace, $key, $default) {
    	if(isset($this->cache[$namespace][$key])) return $this->cache[$namespace][$key];
    	if($this->persistence) return $this->persistence->get($namespace, $key, $default);
        return $default;
    }

    public function delete($namespace, $key) {
		unset($this->cache[$namespace][$key]);
		if($this->persistence) $this->persistence->delete($namespace, $key);
    }

    public function flush() {
		$this->cache = array();
		if($this->persistence) $this->persistence->flush();
    }
}