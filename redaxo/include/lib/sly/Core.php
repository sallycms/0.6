<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_Core {
	private static $instance;
	private $cache;
	private $curClang;
	private $curArticleId;
	private $varTypes;
	private $layout;

	private function __construct() {
		$this->cache = new sly_RedaxoCache();
	}

	/**
	 * Gibt die Instanz des Core Objekts als Singleton zurück
	 *
	 * @return sly_Core  Die singleton Core Instanz
	 */
	public static function getInstance() {
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Hook Methode für Addons um einen Cache an Redaxo anzumelden,
	 * der sich um das Metadatencaching kümmert.
	 *
	 * @param sly_ICache  $cache  Implementierung des Cache.
	 */
	public static function setCache(sly_ICache $cache) {
		self::getInstance()->cache->setPersistence($cache);
	}

	/**
	 * Gibt die angemeldete Cache-Instanz zurück.
	 *
	 * @return sly_ICache  Cache-Instanz
	 */
	public static function cache() {
		return self::getInstance()->cache;
	}

	public static function setCurrentClang($clangId) {
		self::getInstance()->curClang = (int) $clangId;
	}

	public static function getCurrentClang() {
		$instance = self::getInstance();
		
		if (!isset($instance->curClang)) {
			$instance->curClang = rex_request('clang', 'rex-clang-id', self::config()->get('START_CLANG_ID'));
		}
		
		return $instance->curClang;
	}

	public static function setCurrentArticleId($articleId) {
		self::getInstance()->curArticleId = (int) $articleId;
	}

	public static function getCurrentArticleId() {
		$conf     = self::config();
		$instance = self::getInstance();
		
		if (!isset($instance->curArticleId)) {
			if (isset($_REQUEST['article_id'])) {
				$instance->curArticleId = rex_request('article_id', 'rex-article-id', $conf->get('NOTFOUND_ARTICLE_ID'));
			}
			else {
				$instance->curArticleId = $conf->get('START_ARTICLE_ID');
			}
		}
		
		return $instance->curArticleId;
	}

	public static function getTempDir() {
		$conf = self::config();
		return $conf->get('MEDIAFOLDER').DIRECTORY_SEPARATOR.$conf->get('TEMP_PREFIX');
	}

	/**
	 * API Methode um Variabletypen zu setzen.
	 *
	 * @param $varType Klassenname des Variablentyps
	 */
	public static function registerVarType($varType) {
		self::getInstance()->varTypes[] = $varType;
	}

	/**
	 * Gibt immer eine Liste von Instanzen der Variablentypen zurück
	 *
	 * @return array
	 */
	public static function getVarTypes() {
		$instance = self::getInstance();

		if (!isset($instance->varTypes)) $instance->varTypes = array();

		foreach ($instance->varTypes as $idx => $obj) {
			if (is_string($obj)) { // Es hat noch kein Autoloading für diese Klasse stattgefunden
				$obj = new $obj();
				if (!($obj instanceof rex_var)) throw new sly_Exception('VarType '.$instance->varTypes[$idx].' is no inheriting class of rex_var.');
				$instance->varTypes[$idx] = $obj;
			}
		}

		return $instance->varTypes;
	}

	/**
	 * @return sly_Configuration
	 */
	public static function config() {
		return sly_Configuration::getInstance();
	}

	/**
	 * @return sly_Event_Dispatcher
	 */
	public static function dispatcher() {
		return sly_Event_Dispatcher::getInstance();
	}

	/**
	 * gibt ein sly_Layout Instanz zurück
	 *
	 * @param string $type
	 * @return sly_Layout
	 */
	public static function getLayout($type = 'XHTML') {
		$instance = self::getInstance();

		//FIXME: layout type kann bloss einmal pro request angegeben werden,
		// reicht eigentlich auch
		// eventuell könnte man das in der config oder in index.php angeben
		if (!isset($instance->layout)) {
			$className = 'sly_Layout_'.$type;
			$instance->layout = new $className();
		}

		return $instance->layout;
	}

	public static function isBackend() {
		return self::config()->get('SALLY');
	}

	/**
	 * Get persistent registry instance
	 *
	 * @return sly_Registry_Persistent
	 */
	public static function getPersistentRegistry() {
		return sly_Registry_Persistent::getInstance();
	}

	/**
	 * Get temporary registry instance
	 *
	 * @return sly_Registry_Temp
	 */
	public static function getTempRegistry() {
		return sly_Registry_Temp::getInstance();
	}
}
