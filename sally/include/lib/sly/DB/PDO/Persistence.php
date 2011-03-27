<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * PDO Persintence Klasse für eine PDO  Verbindung
 *
 * @author  zozi@webvariants.de
 * @ingroup database
 */
class sly_DB_PDO_Persistence extends sly_DB_Persistence {

	protected $driver;
	private $connection = null;
	private $statement  = null;
	private $currentRow = null;

	public function __construct($driver, $host, $login, $password, $database = null) {
		$this->driver = $driver;
		$this->connection = sly_DB_PDO_Connection::getInstance($driver, $host, $login, $password, $database);
	}

	public function query($query, $data = array()) {
		try {
			$this->currentRow = null;
			$this->statement  = null;
			$this->statement  = $this->connection->getPDO()->prepare($query);

			if ($this->statement->execute($data) === false) {
				$this->error();
			}
		}
		catch (PDOException $e) {
			sly_dump($query);
			sly_dump($data);
			$this->error();
		}

		return true;
	}

	public function insert($table, $values) {
		$sql = $this->getSQLbuilder(self::getPrefix().$table);
		$sql->insert($values);
		$this->query($sql->to_s(), $sql->bind_values());

		return $this->affectedRows();
	}

	public function update($table, $newValues, $where = null) {
		$sql = $this->getSQLbuilder(self::getPrefix().$table);
		$sql->update($newValues);
		$sql->where($where);
		$this->query($sql->to_s(), $sql->bind_values());

		return $this->affectedRows();
	}

	/**
	 *
	 * @param string $table
	 * @param unknown_type $select
	 * @param unknown_type $where
	 * @param unknown_type $group
	 * @param unknown_type $order
	 * @param unknown_type $offset
	 * @param unknown_type $limit
	 * @param unknown_type $having
	 * @param unknown_type $joins
	 *
	 * @return boolean
	 */
	public function select($table, $select = '*', $where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null, $joins = null) {
		$sql = $this->getSQLbuilder(self::getPrefix().$table);
		$sql->select($select);

		if ($where) $sql->where($where);
		if ($group) $sql->group($group);
		if ($having) $sql->having($having);
		if ($order) $sql->order($order);
		if ($offset) $sql->offset($offset);
		if ($limit) $sql->limit($limit);
		if ($joins) $sql->joins($joins);

		return $this->query($sql->to_s(), $sql->bind_values());
	}

	/**
	 * Delete rows from DB
	 *
	 * @param  string $table  table name without system prefix
	 * @param  array  $where  a hash (column => value ...)
	 * @return int            affected rows
	 */
	public function delete($table, $where = null) {
		$sql = $this->getSQLbuilder(self::getPrefix().$table);
		$sql->delete($where);
		$this->query($sql->to_s(), $sql->bind_values());

		return $this->affectedRows();
	}

	public function listTables($find = null) {
		$sql = $this->getSQLbuilder('');
		$sql->list_tables();
		$this->query($sql->to_s(), $sql->bind_values());

		$tables = array();

		foreach ($this as $row) {
			$values = array_values($row);
			$tables[] = reset($values);
		}

		if (is_string($find)) {
			return in_array($find, $tables);
		}

		return $tables;
	}

	public function lastId() {
		return intval($this->connection->getPDO()->lastInsertId());
	}

	public function affectedRows() {
		return $this->statement ? $this->statement->rowCount() : 0;
	}

	private static function getPrefix() {
		static $prefix = null;

		if ($prefix === null) {
			$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		}

		return $prefix;
	}

	public function fetch($table, $select = '*', $where = null, $order = null) {
		$this->select($table, $select, $where, null, $order, null, 1);
		$this->next();
		$data = $this->current();

		if ($this->statement) {
			$this->statement->closeCursor();
		}

		return $data;
	}

	public function magicFetch($table, $select = '*', $where = null, $order = null) {
		$this->select($table, $select, $where, null, $order, null, 1);
		$this->next();
		$data = $this->current();

		if ($this->statement) {
			$this->statement->closeCursor();
		}

		if ($data === false) {
			return false;
		}

		if (count($data) == 1) {
			$ret = array_values($data);
			return $ret[0];
		}

		return $data;
	}

	// =========================================================================
	// Locks
	// =========================================================================

	public function writeLock($tables, $replaceRexPrefix = '') {
		$this->lock($tables, $replaceRexPrefix, 'WRITE');
	}

	public function readLock($tables, $replaceRexPrefix = '') {
		$this->lock($tables, $replaceRexPrefix, 'READ');
	}

	public function unlock() {
		$this->query('UNLOCK TABLES');
	}

	protected function lock($tables, $replaceRexPrefix, $type) {
		if (!is_array($tables)) $tables = array($tables);

		foreach ($tables as &$table) {
			$table = str_replace($replaceRexPrefix, self::getPrefix(), $table).' '.$type;
		}

		$this->query('LOCK TABLES '.implode(', ', $tables));
	}


	// =========================================================================
	// TRANSACTIONS
	// =========================================================================

	/**
	 * Transaktion starten
	 *
	 * Diese Methode startet eine neue Transaktion. Allerdings nur, wenn
	 * $enableSwitch auf true gesetzt ist.
	 */
	public function startTransaction($force = false) {
		if (!$this->connection->isTransRunning() || $force) {
			try {
				$this->connection->getPDO()->beginTransaction();
				$this->connection->setTransRunning(true);
				return true;
			}
			catch (PDOException $e) {
				try {
					if ($force) {
						$this->connection->getPDO()->commit();
						$this->connection->getPDO()->beginTransaction();
						$this->connection->setTransRunning(true);
						return true;
					}

					return false;
				}
				catch (PDOException $e) {
					return false;
				}
			}
		}
	}

	/**
	 * Transaktion beenden
	 *
	 * Diese Methode beendet eine laufende Transaktion. Allerdings nur, wenn
	 * $enableSwitch auf true gesetzt ist.
	 */
	public function doCommit() {
		try {
			$this->connection->getPDO()->commit();
			$this->connection->setTransRunning(false);
			return true;
		}
		catch (PDOException $e) {
			return false;
		}
	}

	/**
	 * Transaktion zurücknehmen
	 *
	 * Diese Methode beendet eine laufende Transaktion. Allerdings nur, wenn
	 * $enableSwitch auf true gesetzt ist.
	 */
	public function doRollBack() {
		try {
			$this->connection->getPDO()->rollBack();
			$this->connection->setTransRunning(false);
			return true;
		}
		catch (PDOException $e) {
			return false;
		}
	}

	public function cleanEndTransaction($e) {
		$this->doRollBack();

		// Exceptions, die nicht von SQL-Problemen herrühren (z.B. InputExceptions),
		// leiten wir weiter nach außen.

		if ($e instanceof Exception && !($e instanceof sly_DB_PDO_Exception)) {
			throw $e;
		}

		// Exceptions, die von SQL-Problemen herrühren, verpacken wir als
		// neue Exception im entsprechenden Applikationskontext.

		$this->error();
	}

	// =========================================================================
	// ERROR UND LOGGING
	// =========================================================================

	protected function error() {
		$message = 'Es trat ein Datenbank-Fehler auf: ';
		throw new sly_DB_PDO_Exception($message.'Fehlercode: '. $this->getErrno() .' '.$this->getError());
	}

	/**
	 * Gibt die letzte Fehlermeldung zurück.
	 *
	 * @return string  die letzte Fehlermeldung
	 */
	protected function getError() {
		if (!$this->statement) {
			return '';
		}

		$info = $this->statement->errorInfo();
		return $info[2]; // Driver-specific error message.
	}

	/**
	 * Gibt den letzten Fehlercode zurück.
	 *
	 * @return int  der letzte Fehlercode oder -1, falls ein Fehler auftrat
	 */
	protected function getErrno() {
		return $this->statement ? $this->statement->errorCode() : -1;
	}

	protected function getSQLbuilder($table) {
		$classname = 'sly_DB_PDO_SQLBuilder_'.strtoupper($this->driver);
		return new $classname($this->connection->getPDO(), $table);
	}


	// =========================================================================
	// ITERATOR-METHODEN
	// =========================================================================

	///@cond INCLUDE_ITERATOR_METHODS

	public function current() {
		return $this->currentRow;
	}

	public function next() {
		$this->currentRow = $this->statement->fetch(PDO::FETCH_ASSOC);

		if ($this->currentRow === false) {
			$this->statement->closeCursor();
			$this->statement = null;
		}
	}

	public function key() {
		return null;
	}

	public function valid() {
		if ($this->statement === null) {
			return false;
		}

		// Wurde noch gar keine Zeile geholt? Dann holen wir das hier nach.
		if ($this->currentRow === null) {
			$this->next();
		}

		return is_array($this->currentRow);
	}

	public function rewind() {
		// Ist in PDO-Statements nicht möglich!
		// Achtung also, wenn über ein Result mehrfach iteriert werden soll.

		if ($this->currentRow !== null) {
			trigger_error('Über ein PDO-Resultset kann nicht mehrfach iteriert werden!', E_USER_WARNING);
		}
	}

	///@endcond
}
