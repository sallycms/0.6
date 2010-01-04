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
 * Dies ist eine Platzhalterimplementierung für ICache
 * Sie ist stabil.   
 * 
 */
class BlackHoleCache implements ICache {
	
    public function __construct() {
		
    }

    public function set($namespace, $key, $value) {

    }

    public function get($namespace, $key, $default) {
        return $default;
    }

    public function delete($namespace, $key) {

    }

    public function flush() {

    }
}