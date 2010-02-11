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

class Core
{
	private static $instance;
	private $cache;
	private $curclang;
	private $varTypes;

	private function __construct()
	{
		$this->cache = new RedaxoCache();
	}

	/**
	 * Gibt die Instanz des Core Objekts als Singleton zurück
	 *
	 * @return Core  Die singleton Core Instanz
	 */
	public static function getInstance()
	{
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Hook Methode für Addons um einen Cache an Redaxo anzumelden,
	 * der sich um das Metadatencaching kümmert.
	 *
	 * @param ICache  $cache  Implementierung des Cache.
	 */
	public static function setCache(ICache $cache)
	{
		self::getInstance()->cache->setPersistence($cache);
	}

	/**
	 * Gibt die angemeldete Cache-Instanz zurück.
	 *
	 * @return ICache  Cache Instanz
	 */
	public static function cache()
	{
		return self::getInstance()->cache;
	}

	public static function getCurrentClang()
	{
		$instance = self::getInstance();
		if(!isset($instance->curclang)){
			$instance->curclang = rex_request('clang', 'rex-clang-id', $REX['START_CLANG_ID']);
		}
		return $instance->curclang;
	}

	public static function getTempDir()
	{
		global $REX;
		return $REX['MEDIAFOLDER'].'/'.$REX['TEMP_PREFIX'];
	}

	/**
	 * API Methode um Variabletypen zu setzen.
	 * Aus Kompatiblitätsgründen in das bekloppte globale $REX array
	 * @param $varType Klassenname des Variablentyps 
	 */
	public static function registerVarType($varType){
		global $REX;
		$REX['VARIABLES'][] = $varType;
	}
	
	/**
	 * Gibt immer eine Liste von Instanzen der Variablentypen zurück
	 * 
	 * @return array 
	 */
	public static function getVarTypes(){
		global $REX;
		if(!isset($REX['VARIABLES']))$REX['VARIABLES'] = array(); 
		foreach ($REX['VARIABLES'] as $idx => $obj) {
			if (is_string($obj)) { // Es hat noch kein Autoloading für diese Klasse stattgefunden
				$obj = new $obj();
				if(!($obj instanceof rex_var)) throw new Exception('VarType '.self::getInstance()->varTypes[$idx].' is no inheriting Class of rex_var.');
				$REX['VARIABLES'][$idx] = $obj;
			}
		}
		return $REX['VARIABLES'];
	}
}