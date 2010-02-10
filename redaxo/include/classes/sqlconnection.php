<?php 

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

class SQLConnection{

	private $connection;
	
	private static $instances = array();
		
	private function __construct($DBID){
		
		global $REX;
		
		$this->connection = null;
		
		$level = error_reporting(0);
		$this->connection = mysql_connect($REX['DB'][$DBID]['HOST'], $REX['DB'][$DBID]['LOGIN'], $REX['DB'][$DBID]['PSW']);

		if (!mysql_select_db($REX['DB'][$DBID]['NAME'], $this->connection)) {
			exit('<span style="color:red;font-family:verdana,arial;font-size:11px;">Es konnte keine Verbindung zur Datenbank hergestellt werden. | Bitte kontaktieren Sie <a href=mailto:'.$REX['ERROR_EMAIL'].'>'.$REX['ERROR_EMAIL'].'</a>. | Danke!</span>');
		}
				
		error_reporting($level);
		
		if (empty($REX['REX_SQL_INIT_'.$DBID])) {
			// ggf. Strict Mode abschalten
			
			mysql_query('SET SQL_MODE = ""', $this->connection);
			
			// Verbindung auf UTF8 trimmen
			
//			if (rex_lang_is_utf8()) {
//				$this->setQuery('SET NAMES utf8');
//			}
		}
		
		$REX['REX_SQL_INIT_'.$DBID] = true;
	}
	
	public static function factory($DBID = 1){
		if(!isset(self::$instances[$DBID])) self::$instances[$DBID] = new self($DBID);
		return self::$instances[$DBID];
	}
	
	public  function getConnection(){
		return $this->connection;
	}
	
}


