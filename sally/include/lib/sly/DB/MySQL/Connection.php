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
 * @deprecated
 * Wir wollen nur eine verbindung pro Datenbank. Wirklich nur eine!!!
 * Da das rex_sql singleton kaum benutzbar ist und dauernd neue Verbindungen aufgemacht werden
 * kapseln wir hier die Verbindung hier in diesem in einer Factory.
 * Kling irgendwie bescheuert, aber ist nicht wirklich zu ändern, da die Codebasis von Redaxo
 * (und wahrscheinlich sehr viele addons) leider kaum Spielraum lässt.
 *
 * @author zozi
 *
 */
class sly_DB_MySQL_Connection{

	private $connection;

	private static $instances = array();

	private function __construct()
	{
		$config = sly_Core::config();
		$db     = $config->get('DATABASE');
		$this->connection = null;

		$level = error_reporting(0);
		$this->connection = mysql_connect($db['HOST'], $db['LOGIN'], $db['PASSWORD']);

		if (!mysql_select_db($db['NAME'], $this->connection)) {
			throw new sly_DB_Exception('Database has gone away!');
		}

		error_reporting($level);

		if (!$config->has('REX_SQL_INIT')) {
			// ggf. Strict Mode abschalten

			mysql_query('SET SQL_MODE = ""', $this->connection);

			// Verbindung auf UTF8 trimmen

//			if (rex_lang_is_utf8()) {
//				$this->setQuery('SET NAMES utf8');
//			}

			$config->set('REX_SQL_INIT', true);
		}
	}

	public static function factory($DBID = 1){
		if(!isset(self::$instances[$DBID])) self::$instances[$DBID] = new self($DBID);
		return self::$instances[$DBID];
	}

	public  function getConnection(){
		return $this->connection;
	}

}
