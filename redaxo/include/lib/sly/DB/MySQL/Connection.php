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
	private $SQLInit = false;

	private static $instances = array();

	private function __construct()
	{
		$config = sly_Core::config();
		$db     = $config->get('DATABASE');
		$this->connection = null;

		$level = error_reporting(0);
		$this->connection = mysql_connect($db['HOST'], $db['LOGIN'], $db['PASSWORD']);

		if (!mysql_select_db($db['NAME'], $this->connection)) {
			exit('<span style="color:red;font-family:verdana,arial;font-size:11px;">Es konnte keine Verbindung zur Datenbank hergestellt werden. | Bitte kontaktieren Sie <a href=mailto:'.$REX['ERROR_EMAIL'].'>'.$REX['ERROR_EMAIL'].'</a>. | Danke!</span>');
		}

		error_reporting($level);

		if (!$this->SQLInit) {
			// ggf. Strict Mode abschalten

			mysql_query('SET SQL_MODE = ""', $this->connection);

			$this->SQLInit = true;
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
