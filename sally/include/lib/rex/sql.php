<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Klasse zur Verbindung und Interatkion mit der Datenbank
 *
 * @ingroup redaxo
 */
class rex_sql {
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
	public $identifier; // Datenbankverbindung
	public $error; // Fehlertext
	public $errno; // Fehlernummer

	public function __construct() {
		$this->identifier = sly_DB_MySQL_Connection::factory()->getConnection();
		$this->flush();
	}

	/**
	 * Gibt den Typ der Abfrage (SQL) zurück
	 *
	 * Mögliche Typen:
	 * - SELECT
	 * - SHOW
	 * - UPDATE
	 * - INSERT
	 * - DELETE
	 * - REPLACE
	 *
	 * @param string $qry  Abfrage
	 */
	public function getQueryType($qry = null) {
		if ($qry === null) {
			$qry = $this->query;
		}

		$qry = trim($qry);

		if (preg_match('/^(SELECT|SHOW|UPDATE|INSERT|DELETE|REPLACE)/i', $qry, $matches)) {
			return strtoupper($matches[1]);
		}

		return false;
	}

	/**
	 * Setzt eine Abfrage (SQL) ab
	 *
	 * @param $query Abfrage
	 * @return boolean True wenn die Abfrage erfolgreich war (keine DB-Errors
	 * auftreten), sonst false
	 */
	public function setQuery($qry, $tablePrefix = '') {
		// Alle Werte zurücksetzen
		$this->flush();

		if (!empty($tablePrefix)) {
			$qry = str_replace($tablePrefix, self::getPrefix(), $qry);
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
			}
		}
		else {
			$this->error = mysql_error($this->identifier);
			$this->errno = mysql_errno($this->identifier);
		}

		return $this->getError() === '';
	}

	/**
	 * Setzt den Tabellennamen
	 *
	 * @param string $table Tabellenname
	 */
	public function setTable($table, $prependWithPrefix = false) {
		if ($prependWithPrefix) {
			$table = self::getPrefix().$table;
		}

		$this->table = $table;
	}

	/**
	 * Setzt den Wert eine Spalte
	 *
	 * @param string $feldname  Spaltenname
	 * @param string $wert      Wert
	 */
	public function setValue($feldname, $wert) {
		$this->values[$feldname] = $wert;
	}

	/**
	 * Setzt ein Array von Werten zugleich
	 *
	 * @param array  $valueArray Ein Array von Werten
	 * @param string $wert       Wert
	 */
	public function setValues($valueArray) {
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
	 *
	 * @param string $feld  Spaltenname des zu prüfenden Feldes
	 * @param string $prop  Wert, der enthalten sein soll
	 */
	public function isValueOf($feld, $prop) {
		if (empty($prop)) {
			return true;
		}

		return strpos($this->getValue($feld), $prop) !== false;
	}

	/**
	 * Setzt die WHERE Bedienung der Abfrage
	 */
	public function setWhere($where) {
		$this->wherevar = 'WHERE '.$where;
	}

	/**
	 * Gibt den Wert einer Spalte im ResultSet zurück
	 * @param $value Name der Spalte
	 * @param [$rowNumber] Zeile aus dem ResultSet
	 */
	public function getValue($feldname, $rowNumber = null) {
		if (isset($this->values[$feldname])) {
			return $this->values[$feldname];
		}

		$row = $this->counter;

		if (is_int($rowNumber)) {
			$row = $rowNumber;
		}

		return mysql_result($this->result, $row, $feldname);
	}

	/**
	 * Prüft, ob eine Spalte im Resultset vorhanden ist
	 * @param $value Name der Spalte
	 */
	public function hasValue($feldname) {
		return in_array($feldname, $this->getFieldnames());
	}

	/**
	 * Prüft, ob das Feld mit dem Namen $feldname Null ist.
	 *
	 * Falls das Feld nicht vorhanden ist,
	 * wird Null zurückgegeben, sonst True/False
	 */
	public function isNull($feldname) {
		if ($this->hasValue($feldname)) {
			return $this->getValue($feldname) === null;
		}

		return null;
	}

	/**
	 * Gibt die Anzahl der Zeilen zurück
	 */
	public function getRows() {
		return $this->rows;
	}

	/**
	 * Gibt die Zeilennummer zurück, auf der sich gerade der
	 * interne Zähler befindet
	 */
	public function getCounter() {
		return $this->counter;
	}

	/**
	 * Gibt die Anzahl der Felder/Spalten zurück
	 */
	public function getFields() {
		return mysql_num_fields($this->result);
	}

	/**
	 * Baut den SET bestandteil mit der
	 * verfügbaren values zusammen und gibt diesen zurück
	 *
	 * @see setValue
	 */
	public function buildSetQuery() {
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
	public function select($fields) {
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
	public function update($successMessage = null) {
		return $this->statusQuery('UPDATE `'.$this->table.'` SET '.$this->buildSetQuery().' '.$this->wherevar, $successMessage);
	}

	/**
	 * Setzt eine Insert-Anweisung auf die angegebene Tabelle
	 * mit den angegebenen Werten ab
	 *
	 * @see #setTable()
	 * @see #setValue()
	 */
	public function insert($successMessage = null) {
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
	public function replace($successMessage = null) {
		return $this->statusQuery('REPLACE INTO `'.$this->table.'` SET '.$this->buildSetQuery().' '.$this->wherevar, $successMessage);
	}

	/**
	 * Setzt eine Delete-Anweisung auf die angegebene Tabelle
	 * mit den angegebenen WHERE Parametern ab
	 *
	 * @see #setTable()
	 * @see #setWhere()
	 */
	public function delete($successMessage = null) {
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
	public function statusQuery($query, $successMessage = null) {
		$res = $this->setQuery($query);

		if ($successMessage) {
			return $res ? $successMessage : $this->getError();
		}

		return $res;
	}

	/**
	 * Stellt alle Werte auf den Ursprungszustand zurück
	 */
	public function flush() {
		$this->flushValues();
		$this->fieldnames = array();

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
	function flushValues() {
		$this->values = array();
	}


	/**
	 * Setzt den Cursor des Resultsets auf die nächst niedrigere Stelle
	 */
	function previous() {
		return --$this->counter;
	}

	/**
	 * Setzt den Cursor des Resultsets auf die nächst höhere Stelle
	 */
	function next() {
		return ++$this->counter;
	}

	/*
	 * Prüft ob das Resultset weitere Datensätze enthält
	 */
	function hasNext() {
		return $this->counter != $this->rows;
	}

	/**
	 * Setzt den Cursor des Resultsets zurück zum Anfang
	 */
	function reset() {
		$this->counter = 0;
	}

	/**
	 * Setzt den Cursor des Resultsets aufs Ende
	 */
	function last() {
		$this->counter = $this->rows - 1;
	}

	/**
	 * Gibt die letzte InsertId zurück
	 */
	public function getLastId() {
		return $this->last_insert_id;
	}

	/**
	 * Gibt die zuletzt aufgetretene Fehlernummer zurück
	 */
	function getErrno() {
		return $this->errno;
	}

	/**
	 * Gibt den zuletzt aufgetretene Fehlernummer zurück
	 */
	function getError() {
		return $this->error;
	}

	/**
	 * Prüft, ob ein Fehler aufgetreten ist
	 */
	public function hasError() {
		return !empty($this->error);
	}

	/**
	 * Gibt die Spaltennamen des ResultSets zurück
	 */
	public function getFieldnames() {
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
	public function escape($value, $delimiter = '') {
		if (!is_numeric($value) && $this->identifier) {
			$value = $delimiter.mysql_real_escape_string($value, $this->identifier).$delimiter;
		}

		return $value;
	}

	/**
	 * Gibt ein SQL Singelton Objekt zurück
	 */
	public static function getInstance($dbID = 1, $createInstance = true) {
		static $instances = array();

		$dbID = 1;

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
	public function freeResult() {
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
	public static function checkDbConnection($host, $login, $pw, $dbname, $createDb = false) {
		$err_msg = true;
		$level   = error_reporting(0);
		$link    = mysql_connect($host, $login, $pw);

		if (!$link) {
			$err_msg = t('setup_021');
		}
		elseif (!mysql_select_db($dbname, $link)) {
			if ($createDb) {
				mysql_query('CREATE DATABASE `'.$dbname.'`', $link);

				if (mysql_error($link) != '') {
					$err_msg = t('setup_022');
				}
			}
			else {
				$err_msg = t('setup_022');
			}
		}

		if ($link) {
			mysql_close($link);
		}

		error_reporting($level);
		return $err_msg;
	}

	public function addGlobalUpdateFields($user = null) {
		if (!$user) {
			$user = sly_Util_User::getCurrentUser()->getLogin();
		}

		$this->setValue('updatedate', time());
		$this->setValue('updateuser', $user);
	}

	public function addGlobalCreateFields($user = null) {
		if (!$user) {
			$user = sly_Util_User::getCurrentUser()->getLogin();
		}

		$this->setValue('createdate', time());
		$this->setValue('createuser', $user);
	}

	public static function isValid($object) {
		return is_object($object) && $object instanceof self;
	}

	public static function getPrefix() {
		static $prefix = null;

		if ($prefix === null) {
			$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		}

		return $prefix;
	}
}
