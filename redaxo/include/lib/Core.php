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

class sly_Core
{
	private static $instance;
	private $cache;
	private $curClang;
	private $curArticleId;
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
		if(!isset($instance->curClang)){
			$instance->curClang = rex_request('clang', 'rex-clang-id', self::config()->get('START_CLANG_ID'));
		}
		return $instance->curClang;
	}

	public static function getCurrentArticleId()
	{
		$conf = self::config();
		
		$instance = self::getInstance();
		if(!isset($instance->curArticleId)){
			
			if(isset($_REQUEST['article_id'])) {
				$instance->curArticleId = rex_request('article_id','rex-article-id', $conf->get('NOTFOUND_ARTICLE_ID'));
			} else {
				$instance->curArticleId = $conf->get('START_ARTICLE_ID');
			}
		}
		return $instance->curArticleId;
	}

	public static function getTempDir()
	{
		$conf = self::config();
		return $conf->get('MEDIAFOLDER') . DIRECTORY_SEPARATOR . $conf->get('TEMP_PREFIX');
	}

	/**
	 * API Methode um Variabletypen zu setzen.
	 * Aus Kompatiblitätsgründen in das bekloppte globale $REX array
	 * @param $varType Klassenname des Variablentyps 
	 */
	public static function registerVarType($varType){
		
		self::getInstance()->varTypes[] = $varType;
	}
	
	/**
	 * Gibt immer eine Liste von Instanzen der Variablentypen zurück
	 * 
	 * @return array 
	 */
	public static function getVarTypes(){
		$instance = self::getInstance();

		if(!isset($instance->varTypes)) $instance->varTypes = array();
		foreach ($instance->varTypes as $idx => $obj) {
			if (is_string($obj)) { // Es hat noch kein Autoloading für diese Klasse stattgefunden
				$obj = new $obj();
				if(!($obj instanceof rex_var)) throw new Exception('VarType '.$instance->varTypes[$idx].' is no inheriting Class of rex_var.');
				$instance->varTypes[$idx] = $obj;
			}
		}
		return $instance->varTypes;
	}

	/**
	 * 
	 * @return sly_Configuration
	 */
	public static function config(){
		return sly_Configuration::getInstance();
	}
}