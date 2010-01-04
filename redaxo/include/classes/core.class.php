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

	private function __construct()
	{
		global $REX;
		$this->curclang = rex_request('clang', 'rex-clang-id', $REX['START_CLANG_ID']);
		$this->cache = new BlackHoleCache();
	}

	/**
	 * Gibt die Instanz des Core Objekts als Singleton zurück
	 *
	 * @return Core  Die singleton Core Instanz
	 */
	public static function getInstance()
	{
		if (!self::$instance) self::$instance = new Core();
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
		self::getInstance()->cache = $cache;
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
		return self::getInstance()->curclang;
	}

	public static function getTempDir()
	{
		global $REX;
		return $REX['MEDIAFOLDER'].'/'.$REX['TEMP_PREFIX'];
	}
}