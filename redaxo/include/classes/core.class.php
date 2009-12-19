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

class Core {
	private static $instance;
	private $cache;
	
	private function __construct() {
	}
	
	/**
	 * Gibt die Instanz des Core Objekts als Singleton zurück
	 * 
	 * @return Core  Die singleton Core Instanz
	 */
	public static function getInstance() {
		if (!self::$instance) self::$instance = new Core();
		return self::$instance;
	}

	/**
	 * Hook Methode für Addons um einen Cache an Redaxo anzumelden, 
	 * der sich um das Metadatencaching kümmert.
	 * 
	 * @param ICache  $cache  Implementierung des Cache.
	 */
	public function setCache(ICache $cache) {
		$this->cache = $cache;
	}
	
	/**
	 * Gibt die angemeldete Cache-Instanz zurück.
	 * 
	 * Bricht mit einem E_USER_ERROR ab, wenn darauf zugegriffen 
	 * wird, ohne dass ein Cache gesetzt wurde. Sollte die Gefahr 
	 * bestehen, dass der Zugriff auf den Cache vor dem Laden des 
	 * Caching Addons, sollte unbedingt hasCache() augfgerufen 
	 * werden.  
	 * 
	 * @return ICache  Cache Instanz
	 */
	public function getCache() {
		if (!isset($this->cache)) {
			throw new Exception('muh');
			//trigger_error('Cache is not yet available. Call getCache() later. :)', E_USER_ERROR);
		}
		return $this->cache;
	}
	
	/**
	 * Prüft, ob bereits ein Cache gesetzt wurde.
	 * 
	 * @return boolean
	 */
	public function hasCache(){
		return !self::getInstance()->cache == null;
	}

} 