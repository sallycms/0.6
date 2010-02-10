<?php

$filename = $REX['INCLUDE_PATH'].'/addons/developer_utils/classes/class.querylogger.php';

if (file_exists($filename)) {
	require_once $REX['INCLUDE_PATH'].'/addons/developer_utils/classes/class.querylogger.php';
	$filename = $REX['MEDIAFOLDER'].'/'.$REX['TEMP_PREFIX'].'/eh.querylogging';
	if (file_exists($filename)) _WV_QueryLogger::enableLogging(file_get_contents($filename));
}

unset($filename);

/**
 * Klasse zur Verbindung und Interatkion mit der Datenbank
 * @version svn:$Id$
 */
class rex_sql
{
	/*
	 * Da leider massenhaft Code von REDAXO direkt auf die Eigenschaften
	 * zugreift, können wir sie nicht protected setzen. REDAXO können wir noch
	 * patchen, aber nicht fremde AddOns.
	 */
	 
	public $values; // Werte von setValue
	public $fieldnames; // Spalten im ResultSet

	public $table; // Tabelle setzen
	public $wherevar; // WHERE Bediengung
	public $query; // letzter Query String
	public $counter; // ResultSet Cursor
	public $rows; // anzahl der treffer
	public $result; // ResultSet
	public $last_insert_id; // zuletzt angelegte auto_increment nummer
	public $debugsql; // debug schalter
	public $identifier; // Datenbankverbindung
	public $DBID; // ID der Verbindung

	public $error; // Fehlertext
	public $errno; // Fehlernummer
	
	private static $identifiers = array(1 => null, 2 => null);

	public function __construct($DBID = 1)
	{
		global $REX;

		$this->debug      = false;
		$this->identifier = null;
		$this->selectDB($DBID);
		$this->flush();
	}

	/**
	 * Stellt die Verbindung zur Datenbank her
	 */
	public function selectDB($DBID, $forceReconnect = false)
	{
		$this->identifier = SQLConnection::factory($DBID)->getConnection();
		$this->DBID       = $DBID;
	}

	/**
	 * Gibt die DatenbankId der Abfrage (SQL) zurück,
	 * oder false wenn die Abfrage keine DBID enthält
	 *
	 * @param $query Abfrage
	 */
	public function getQueryDBID($qry = null)
	{
		if (!$qry) {
			if (!isset($this)) { // Nur bei angelegtem Object
				return null;
			}
			
			$qry = $this->query;
		}

		$qry = trim($qry);

		if (preg_match('/\(DB([1-9])\)/i', $qry, $matches)) {
			return $matches[1];
		}

		return false;
	}

	/**
	* Entfernt die DBID aus einer Abfrage (SQL) und gibt die DBID zurück falls
	* vorhanden, sonst false
	*
	* @param $query Abfrage
	*/
	public static function stripQueryDBID(&$qry)
	{
		$qry = trim($qry);

		if (($qryDBID = self::getQueryDBID($qry)) !== false) {
			$qry = substr($qry, 6);
		}

		return $qryDBID;
	}

	/**
	 * Gibt den Typ der Abfrage (SQL) zurück,
	 * oder false wenn die Abfrage keinen Typ enthält
	 *
	 * Mögliche Typen:
	 * - SELECT
	 * - SHOW
	 * - UPDATE
	 * - INSERT
	 * - DELETE
	 * - REPLACE
	 *
	 * @param $query Abfrage
	 */
	public function getQueryType($qry = null)
	{
		if (!$qry) {
			if (!isset($this)) { // Nur bei angelegtem Object
				return null;
			}
			
			$qry = $this->query;
		}

		$qry = trim($qry);
		self::stripQueryDBID($qry); // DBID aus dem Query herausschneiden, falls vorhanden

		if (preg_match('/^(SELECT|SHOW|UPDATE|INSERT|DELETE|REPLACE)/i', $qry, $matches)) {
			return strtoupper($matches[1]);
		}

		return false;
	}

	/**
	 * Hilfsmethode für genau eine Zeile
	 *
	 * Diese Methode dient dazu, genau eine Zeile zu holen. Sie gibt im
	 * Erfolgsfall kein true, sondern die geholte Zeile zurück, wodurch ein
	 * Aufruf von row() entfällt. Sollte das Ergebnis nur eine Spalte haben, so
	 * wird direkt dieser Wert zurückgeliefert (und kein Array mit einem
	 * Element).
	 *
	 * @param  string $what   Spalten, die geholt werden sollen
	 * @param  string $from   Tabelle, aus der gelesen werden soll
	 * @param  string $where  WHERE-Kriterium
	 * @param  int    $mode   der Modus, in dem die Zeilen dann zurückgegeben werden sollen
	 * @return mixed          false im Falle eines Fehlers, sonst mixed oder ein Array (je nach Spaltenanzahl)
	 */
	public static function fetch($what, $from, $where = '1', $mode = MYSQL_ASSOC)
	{
		global $REX;
		
		// Verbindung herstellen
		self::getInstance(1);

		$query = sprintf(
			'SELECT %s FROM %s%s WHERE %s LIMIT 1',
			$what,
			$REX['TABLE_PREFIX'],
			$from,
			$where
		);

		$result = mysql_query($query);
		
		if ($result === false || mysql_num_rows($result) == 0) {
			return false;
		}

		$row = array();

		switch ($mode) {
			case MYSQL_BOTH : $row = mysql_fetch_array($result); break;
			case MYSQL_NUM  : $row = mysql_fetch_row($result); break;
			case MYSQL_ASSOC:
			default         : $row = mysql_fetch_assoc($result);
		}

		mysql_free_result($result);

		if (count($row) == 1) {
			$ret = array_values($row);
			return $ret[0];
		}
		else {
			return $row;
		}
	}

	/**
	 * Liefert ein Array zurück
	 *
	 * Diese Methode läuft über ein Resultset und gibt ein
	 * normales Array mit dem Wert zurück. Es darf dazu nur
	 * ein einzelnes Feld selektiert werden. Werden zwei
	 * Felder selektiert, so ist das erste der Schlüssel und
	 * der zweite der Wert des Ergebnisarrays.
	 *
	 * @param  string $query  die auszuführende Abfrage
	 * @return array          ein Array mit den Werten
	 */
	public static function getArrayEx($query, $tablePrefix = '')
	{
		// Verbindung herstellen
		self::getInstance(1);
		
		if (!empty($tablePrefix)) {
			global $REX;
			$query = str_replace($tablePrefix, $REX['TABLE_PREFIX'], $query);
		}
		
		$result = mysql_query($query);

		if ($result === false || mysql_num_rows($result) == 0 || mysql_num_fields($result) == 0) {
			if ($result) mysql_free_result($result);
			return array();
		}

		$res  = array();
		$cols = mysql_num_fields($result);

		while ($row = mysql_fetch_assoc($result)) {
			$key = reset($row);

			if ($cols == 1) {
				$res[] = $key;
			}
			elseif ($cols == 2) {
				$res[$key] = next($row);
			}
			else {
				$columns = array_slice(array_keys($row), 1);

				foreach ($columns as $col) {
					$res[$key][$col] = $row[$col];
				}
			}
		}

		mysql_free_result($result);
		return $res;
	}

	/**
	 * Setzt eine Abfrage (SQL) ab, wechselt die DBID falls vorhanden
	 *
	 * @param $query Abfrage
	 * @return boolean True wenn die Abfrage erfolgreich war (keine DB-Errors
	 * auftreten), sonst false
	 */
	public function setDBQuery($qry)
	{
		// Verbindung herstellen
		$this->selectDB($this->DBID);
		
		if (($qryDBID = self::stripQueryDBID($qry)) !== false) {
			$this->selectDB($qryDBID);
		}

		return $this->setQuery($qry);
	}

	/**
	 * Setzt eine Abfrage (SQL) ab
	 *
	 * @param $query Abfrage
	 * @return boolean True wenn die Abfrage erfolgreich war (keine DB-Errors
	 * auftreten), sonst false
	 */
	public function setQuery($qry, $tablePrefix = '')
	{
		// Alle Werte zurücksetzen
		$this->flush();
		
		// Verbindung herstellen
		//$this->selectDB($this->DBID);
		
		if (!empty($tablePrefix)) {
			global $REX;
			$qry = str_replace($tablePrefix, $REX['TABLE_PREFIX'], $qry);
		}

		$qry = trim($qry);
		$this->query = $qry;

		$before       = microtime(true);
		$this->result = @mysql_query($qry, $this->identifier);
		$duration     = microtime(true) - $before;

		if ($this->result) {
			if (($qryType = $this->getQueryType()) !== false) {
				switch ($qryType) {
					case 'SELECT':
					case 'SHOW':
					
						$this->rows = mysql_num_rows($this->result);
						break;
					
					case 'REPLACE':
					case 'DELETE':
					case 'UPDATE':
					
						$this->rows = mysql_affected_rows($this->identifier);
						break;
					
					case 'INSERT':
					
						$this->rows = mysql_affected_rows($this->identifier);
						$this->last_insert_id = mysql_insert_id($this->identifier);
				}

				if (class_exists('_WV_QueryLogger')) _WV_QueryLogger::log($qry, $duration, $this->rows);
			}
		}
		else {
			$this->error = mysql_error($this->identifier);
			$this->errno = mysql_errno($this->identifier);

			if (class_exists('_WV_QueryLogger')) _WV_QueryLogger::log($qry, $duration, _WV_QueryLogger::ERROR);
		}

		if ($this->debugsql || $this->error != '') {
			$this->printError($qry);
		}

		return $this->getError() === '';
	}

	/**
	 * Setzt den Tabellennamen
	 *
	 * @param $table Tabellenname
	 */
	public function setTable($table, $prependWithPrefix = false)
	{
		if ($prependWithPrefix) {
			global $REX;
			$table = $REX['TABLE_PREFIX'].$table;
		}
		
		$this->table = $table;
	}

	/**
	 * Setzt den Wert eine Spalte
	 *
	 * @param $feldname Spaltenname
	 * @param $wert Wert
	 */
	public function setValue($feldname, $wert)
	{
		$this->values[$feldname] = $wert;
	}

	/**
	 * Setzt ein Array von Werten zugleich
	 *
	 * @param $valueArray Ein Array von Werten
	 * @param $wert Wert
	 */
	public function setValues($valueArray)
	{
		if (is_array($valueArray)) {
			foreach ($valueArray as $name => $value) {
				$this->setValue($name, $value);
			}
			
			return true;
		}
		
		return false;
	}

	/**
	 * Prüft den Wert einer Spalte der aktuellen Zeile ob ein Wert enthalten ist
	 * @param $feld Spaltenname des zu prüfenden Feldes
	 * @param $prop Wert, der enthalten sein soll
	 */
	public function isValueOf($feld, $prop)
	{
		if (empty($prop)) {
			return true;
		}
		
		return strpos($this->getValue($feld), $prop) !== false;
	}

	/**
	 * Setzt die WHERE Bedienung der Abfrage
	 */
	public function setWhere($where)
	{
		$this->wherevar = 'WHERE '.$where;
	}

	/**
	 * Gibt den Wert einer Spalte im ResultSet zurück
	 * @param $value Name der Spalte
	 * @param [$rowNumber] Zeile aus dem ResultSet
	 */
	public function getValue($feldname, $rowNumber = null)
	{
		if (isset($this->values[$feldname])) {
			return $this->values[$feldname];
		}

		$row = $this->counter;
		
		if (is_int($rowNumber)) {
			$row = $rowNumber;
		}
		if($feldname == 'category_id') debug_print_backtrace();
		return mysql_result($this->result, $row, $feldname);
	}

	/**
	 * Prüft, ob eine Spalte im Resultset vorhanden ist
	 * @param $value Name der Spalte
	 */
	public function hasValue($feldname)
	{
		return in_array($feldname, $this->getFieldnames());
	}

	/**
	 * Prüft, ob das Feld mit dem Namen $feldname Null ist.
	 *
	 * Falls das Feld nicht vorhanden ist,
	 * wird Null zurückgegeben, sonst True/False
	 */
	public function isNull($feldname)
	{
		if ($this->hasValue($feldname)) {
			return $this->getValue($feldname) === null;
		}

		return null;
	}

	/**
	 * Gibt die Anzahl der Zeilen zurück
	 */
	public function getRows()
	{
		return $this->rows;
	}

	/**
	 * Gibt die Zeilennummer zurück, auf der sich gerade der
	 * interne Zähler befindet
	 */
	public function getCounter()
	{
		return $this->counter;
	}

	/**
	 * Gibt die Anzahl der Felder/Spalten zurück
	 */
	public function getFields()
	{
		return mysql_num_fields($this->result);
	}

	/**
	 * Baut den SET bestandteil mit der
	 * verfügbaren values zusammen und gibt diesen zurück
	 *
	 * @see setValue
	 */
	public function buildSetQuery()
	{
		$sets = array();
		
		if (is_array($this->values)) {
			foreach ($this->values as $fld_name => $value) {
				// Bei <tabelle>.<feld> Notation '.' ersetzen,
				// da sonst `<tabelle>.<feld>` entsteht
				
				if (strpos($fld_name, '.') !== false) {
					$fld_name = str_replace('.', '`.`', $fld_name);
				}

				if ($value === null) {
					$sets[] = '`'.$fld_name.'` = NULL';
				}
				else {
					$sets[] = '`'.$fld_name.'` = \''.$value.'\'';
				}

				// Da Werte via POST/GET schon mit magic_quotes escaped werden,
				// brauchen wir hier nicht mehr escapen
//				$qry .= '`' . $fld_name . '`=' . $this->escape($value);
			}
		}

		return implode(', ', $sets);
	}

	/**
	 * Setzt eine Select-Anweisung auf die angegebene Tabelle
	 * mit den WHERE Parametern ab
	 *
	 * @see #setTable()
	 * @see #setWhere()
	 */
	public function select($fields)
	{
		return $this->setQuery('SELECT '.$fields.' FROM `'.$this->table.'` '.$this->wherevar);
	}

	/**
	 * Setzt eine Update-Anweisung auf die angegebene Tabelle
	 * mit den angegebenen Werten und WHERE Parametern ab
	 *
	 * @see #setTable()
	 * @see #setValue()
	 * @see #setWhere()
	 */
	public function update($successMessage = null)
	{
		return $this->statusQuery('UPDATE `'.$this->table.'` SET '.$this->buildSetQuery().' '.$this->wherevar, $successMessage);
	}

	/**
	 * Setzt eine Insert-Anweisung auf die angegebene Tabelle
	 * mit den angegebenen Werten ab
	 *
	 * @see #setTable()
	 * @see #setValue()
	 */
	public function insert($successMessage = null)
	{
		return $this->statusQuery('INSERT INTO `'.$this->table.'` SET '.$this->buildSetQuery(), $successMessage);
	}

	/**
	 * Setzt eine Replace-Anweisung auf die angegebene Tabelle
	 * mit den angegebenen Werten ab
	 *
	 * @see #setTable()
	 * @see #setValue()
	 * @see #setWhere()
	 */
	public function replace($successMessage = null)
	{
		return $this->statusQuery('REPLACE INTO `'.$this->table.'` SET '.$this->buildSetQuery().' '.$this->wherevar, $successMessage);
	}

	/**
	 * Setzt eine Delete-Anweisung auf die angegebene Tabelle
	 * mit den angegebenen WHERE Parametern ab
	 *
	 * @see #setTable()
	 * @see #setWhere()
	 */
	public function delete($successMessage = null)
	{
		return $this->statusQuery('DELETE FROM `'.$this->table.'` '.$this->wherevar, $successMessage);
	}

	/**
	 * Setzt den Query $query ab.
	 *
	 * Wenn die Variable $successMessage gefüllt ist, dann wird diese bei
	 * erfolgreichem absetzen von $query zurückgegeben, sonst die MySQL
	 * Fehlermeldung
	 *
	 * Wenn die Variable $successMessage nicht gefüllt ist, verhält sich diese
	 * Methode genauso wie setQuery()
	 *
	 * Beispiel:
	 *
	 * <code>
	 * $sql = new rex_sql();
	 * $message = $sql->statusQuery(
	 *    'INSERT  INTO abc SET a="ab"',
	 *    'Datensatz  erfolgreich eingefügt');
	 * </code>
	 *
	 *  anstatt von
	 *
	 * <code>
	 * $sql = new rex_sql();
	 * if($sql->setQuery('INSERT INTO abc SET a="ab"'))
	 *   $message  = 'Datensatz erfolgreich eingefügt');
	 * else
	 *   $message  = $sql- >getError();
	 * </code>
	 */
	public function statusQuery($query, $successMessage = null)
	{
		$res = $this->setQuery($query);
		
		if ($successMessage) {
			return $res ? $successMessage : $this->getError();
		}
		
		return $res;
	}

	/**
	 * Stellt alle Werte auf den Ursprungszustand zurück
	 */
	public function flush()
	{
		$this->flushValues();
		$this->fieldnames = array ();

		$this->table          = '';
		$this->wherevar       = '';
		$this->query          = '';
		$this->counter        = 0;
		$this->rows           = 0;
		$this->result         = '';
		$this->last_insert_id = '';
		$this->error          = '';
		$this->errno          = '';
	}

	/**
	 * Stellt alle Values, die mit setValue() gesetzt wurden, zurück
	 *
	 * @see #setValue(), #getValue()
	 */
	function flushValues()
	{
		$this->values = array();
	}


	/**
	 * Setzt den Cursor des Resultsets auf die nächst niedrigere Stelle
	 */
	function previous()
	{
		return --$this->counter;
	}

	/**
	 * Setzt den Cursor des Resultsets auf die nächst höhere Stelle
	 */
	function next()
	{
		return ++$this->counter;
	}

	/*
	 * Prüft ob das Resultset weitere Datensätze enthält
	 */
	function hasNext()
	{
		return $this->counter != $this->rows;
	}

	/**
	 * Setzt den Cursor des Resultsets zurück zum Anfang
	 */
	function reset()
	{
		$this->counter = 0;
	}

	/**
	 * Setzt den Cursor des Resultsets aufs Ende
	 */
	function last()
	{
		$this->counter = ($this->rows - 1);
	}

	/**
	 * Gibt die letzte InsertId zurück
	 */
	public function getLastId()
	{
		return $this->last_insert_id;
	}

	/**
	 * Lädt das komplette Resultset in ein Array und gibt dieses zurück und
	 * wechselt die DBID falls vorhanden
	 *
	 * @access public
	 * @param string $sql Abfrage
	 * @param string $fetch_type Default: MYSQL_ASSOC; weitere: MYSQL_NUM, MYSQL_BOTH
	 * @return array
	 */
	public function getDBArray($sql = '', $fetch_type = MYSQL_ASSOC)
	{
		return $this->_getArray($sql, $fetch_type, 'DBQuery');
	}

	/**
	 * Lädt das komplette Resultset in ein Array und gibt dieses zurück
	 *
	 * @access public
	 * @param string $sql Abfrage
	 * @param string $fetch_type Default: MYSQL_ASSOC; weitere: MYSQL_NUM, MYSQL_BOTH
	 * @return array
	 */
	public function getArray($sql = '', $fetch_type = MYSQL_ASSOC)
	{
		return $this->_getArray($sql, $fetch_type);
	}

	/**
	 * Hilfsfunktion
	 *
	 * @access private
	 * @see getArray()
	 * @see getDBArray()
	 * @param string $sql Abfrage
	 * @param string $fetch_type Default: MYSQL_ASSOC, MYSQL_NUM, MYSQL_BOTH
	 * @param string $qryType void oder DBQuery
	 * @return array
	 */
	public function _getArray($sql, $fetch_type, $qryType = 'default')
	{
		if ($sql != '') {
			switch ($qryType)
			{
				case 'DBQuery': $this->setDBQuery($sql); break;
				default       : $this->setQuery($sql);
			}
		}


		$data  = array();
		$level = error_reporting(0);

		while ($row = mysql_fetch_array($this->result, $fetch_type)) {
			$data[] = $row;
		}

		error_reporting($level);
		return $data;
	}

	/**
	 * Gibt die zuletzt aufgetretene Fehlernummer zurück
	 */
	function getErrno()
	{
		return $this->errno;
	}

	/**
	 * Gibt den zuletzt aufgetretene Fehlernummer zurück
	 */
	function getError()
	{
		return $this->error;
	}

	/**
	 * Prüft, ob ein Fehler aufgetreten ist
	 */
	public function hasError()
	{
		return !empty($this->error);
	}

	/**
	 * Gibt die letzte Fehlermeldung aus
	 */
	public function printError($query)
	{
		if ($this->debugsql) {
			$newline = "<br />\n";
			$error   = $this->getError();
			
			print '<hr />'."\n";
			print 'Query: '.nl2br(htmlspecialchars($query)).$newline;

			if ($this->getRows() > 0) {
				print 'Affected Rows: '.$this->getRows().$newline;
			}
			
			if (!empty($error)) {
				print 'Error Message: '.htmlspecialchars($error).$newline;
				print 'Error Code: '.$this->getErrno().$newline;
			}
		}
	}

	/**
	 * Setzt eine Spalte auf den nächst möglich auto_increment Wert
	 * @param $field Name der Spalte
	 */
	public function setNewId($field)
	{
		$sql = new self();
		$id  = false;
		
		if ($sql->setQuery('SELECT `'.$field.'` FROM `'.$this->table.'` ORDER BY `'.$field.'` DESC LIMIT 1')) {
			if ($sql->getRows() == 0) {
				$id = 1;
			}
			else {
				$id = $sql->getValue($field) + 1;
			}

			$this->setValue($field, $id);
		}

		$sql = null;
		return $id;
	}

	/**
	 * Gibt die Spaltennamen des ResultSets zurück
	 */
	public function getFieldnames()
	{
		if (empty($this->fieldnames)) {
			for ($i = 0; $i < $this->getFields(); $i++) {
				$this->fieldnames[] = mysql_field_name($this->result, $i);
			}
		}
		
		return $this->fieldnames;
	}

	/**
	 * Escaped den übergeben Wert für den DB Query
	 *
	 * @param $value den zu escapenden Wert
	 * @param [$delimiter] Delimiter der verwendet wird, wenn es sich bei $value
	 * um einen String handelt
	 */
	public function escape($value, $delimiter = '')
	{
		if (!is_numeric($value) && $this->identifier) {
			$value = $delimiter.mysql_real_escape_string($value, $this->identifier).$delimiter;
		}
		
		return $value;
	}

	public static function showTables($dbID = 1)
	{
		global $REX;

		$sql = new self($dbID);
		$sql->setQuery('SHOW TABLES');

		$tables = array();
		$dbName = $REX['DB'][$dbID]['NAME'];
		
		while ($sql->hasNext()) {
			$tables[] = $sql->getValue('Tables_in_'.$dbName);
			$sql->next();
		}

		$sql = null;
		return $tables;
	}

	public static function showColumns($table, $dbID = 1)
	{
		$sql = new self($dbID);
		$sql->setQuery('SHOW COLUMNS FROM `'.$table.'`');

		$columns = array();
		
		while ($sql->hasNext()) {
			$columns[] = array(
				'name'    => $sql->getValue('Field'),
				'type'    => $sql->getValue('Type'),
				'null'    => $sql->getValue('Null'),
				'key'     => $sql->getValue('Key'),
				'default' => $sql->getValue('Default'),
				'extra'   => $sql->getValue('Extra')
			);
			
			$sql->next();
		}

		$sql = null;
		return $columns;
	}

	/**
	 * Gibt die Serverversion zurück
	 */
	public static function getServerVersion()
	{
		return $this->getArray('SELECT VERSION() AS v');
	}

	/**
	 * Gibt ein SQL Singelton Objekt zurück
	 */
	public static function getInstance($dbID = 1, $createInstance = true)
	{
		static $instances = array();
		
		$dbID = (int) $dbID;

		if (!empty($instances[$dbID])) {
			$instances[$dbID]->flush();
		}
		elseif ($createInstance) {
			$instances[$dbID] = new self($dbID);
		}

		return empty($instances[$dbID]) ? null : $instances[$dbID];
	}

	/**
	 * Gibt den Speicher wieder frei
	 */
	public function freeResult()
	{
		if (is_resource($this->result)) {
			mysql_free_result($this->result);
			return true;
		}
		
		return false;
	}

	/**
	 * Prueft die uebergebenen Zugangsdaten auf gueltigkeit und legt ggf. die
	 * Datenbank an
	 */
	public static function checkDbConnection($host, $login, $pw, $dbname, $createDb = false)
	{
		global $I18N;

		$err_msg = true;
		$level   = error_reporting(0);
		$link    = mysql_connect($host, $login, $pw);
		
		if (!$link) {
			$err_msg = $I18N->msg('setup_021');
		}
		elseif (!mysql_select_db($dbname, $link)) {
			if ($createDb) {
				mysql_query('CREATE DATABASE `'.$dbname.'`', $link);
				
				if (mysql_error($link) != '') {
					$err_msg = $I18N->msg('setup_022');
				}
			}
			else {
				$err_msg = $I18N->msg('setup_022');
			}
		}

		if($link) {
			mysql_close($link);
		}
		
		error_reporting($level);
		return $err_msg;
	}

	/**
	 * Schließt die Verbindung zum DB Server
	 */
	public static function disconnect($dbID = 1)
	{
		global $REX;

		// Alle Connections schließen
		
		if ($dbID === null) {
			foreach ($REX['DB'] as $dbID => $dbSettings) {
				self::disconnect($dbID);
			}

			return;
		}

		if (!$REX['DB'][$dbID]['PERSISTENT']) {
			$db = self::getInstance($dbID, false);

			if (self::isValid($db) && is_resource($db->identifier)) {
				mysql_close($db->identifier);
				self::$identifiers[$dbID] = null;
			}
		}
	}

	public function addGlobalUpdateFields($user = null)
	{
		global $REX;

		if (!$user) {
			$user = $REX['USER']->getValue('login');
		}

		$this->setValue('updatedate', time());
		$this->setValue('updateuser', $user);
	}

	public function addGlobalCreateFields($user = null)
	{
		global $REX;

		if (!$user) {
			$user = $REX['USER']->getValue('login');
		}

		$this->setValue('createdate', time());
		$this->setValue('createuser', $user);
	}

	public static function isValid($object)
	{
		return is_object($object) && $object instanceof self;
	}
}