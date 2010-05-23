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

/**
 * PDO Persintence Klasse für eine PDO  Verbindung
 * 
 * @author zozi@webvariants.de
 *
 */
class sly_DB_PDO_Persistence extends sly_DB_Persistence{
	const LOG_UNKNOWN = -1;
	const LOG_ERROR   = -2;
	
	private $connection  = null;
	private $statement    = null;
	private $currentRow   = null;
	
	protected function __construct() {
		$this->connection = sly_DB_PDO_Connection::getInstance();
	}
	
	public function query($query, $data = array()){
		
		try{
			$start = microtime(true);
			$this->currentRow = null;
			$this->statement = null;
			$this->statement = $this->connection->getConnection()->prepare($query);
			if($this->statement->execute($data) === false){
				$time = microtime(true) - $start;
                self::log($query, $time, self::LOG_ERROR);
                $this->error();
			}
			
			$time = microtime(true) - $start;
			self::log($query, $time, $this->affectedRows());
			
		}catch(PDOException $e){
			$time = microtime(true) - $start;
			self::log($query, $time, self::LOG_ERROR);
            $this->error();
		}
		return true;
	} 
	
	public function insert($table, $values) {
		$sql = $this->connection->getSQLbuilder(self::getPrefix().$table);
		$sql->insert($values);
        $this->query($sql->to_s(), $sql->bind_values());
        
        return $this->affectedRows();
    }
	
    public function update($table, $newValues, $where = null){
    	$sql = $this->connection->getSQLbuilder(self::getPrefix().$table);
    	$sql->update($newValues);
    	$sql->where($where);
    	$this->query($sql->to_s(), $sql->bind_values());
    	
    	return $this->affectedRows();
    }
    
    public function select($table, $select = '*', $where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null, $joins = null) {
		$sql = $this->connection->getSQLbuilder(self::getPrefix().$table);
		$sql->select($select);
		if($where) $sql->where($where);
		if($group) $sql->group($group);
		if($having) $sql->having($having);
		if($order) $sql->order($order);
		if($offset) $sql->offset($offset);
		if($limit) $sql->limit($limit);
		if($joins) $sql->joins($joins);
		
    	return $this->query($sql->to_s(), $sql->bind_values());
    }
    
    /**
     * Delete Rows fron DB
     * 
     * @param $table TableName without prefix
     * @param $where a hash (columnname => value ...)
     * @return int affected rows
     */
    public function delete($table, $where = null){
    	$sql = $this->connection->getSQLbuilder(self::getPrefix().$table);
    	$sql->delete($where);
    	$this->query($sql->to_s(), $sql->bind_values());
    	
    	return $this->affectedRows();
    }
    
	public function listTables($find = null)
	{
		$sql = $this->connection->getSQLbuilder('');
		$sql->list_tables();
		$this->query($sql->to_s(), $sql->bind_values());
		
		$tables = array();
		
		foreach ($this as $row) {
			$tables[] = reset(array_values($row));
		}
		
		if (is_string($find)) {
			return in_array($find, $tables);
		}
		
		return $tables;
	}
    
    public function lastId() {
		return intval($this->connection->getConnection()->lastInsertId());
    }
    
    private function affectedRows() {
        return $this->statement ? $this->statement->rowCount() : 0;
    }
    
 	private static function getPrefix() {
        global $REX;
        return $REX['TABLE_PREFIX'];
    }
	 
	public function fetch($table, $select = '*', $where = null, $order = null) {
		$this->select($table, $select, $where, null, $order, null, 1);
		$this->next();
		$data = $this->current();
		
		return $data;
    }
	 
	public function magicFetch($table, $select = '*', $where = null, $order = null)
	{
		$this->select($table, $select, $where, null, $order, null, 1);
		$this->next();
		$data = $this->current();
		
		$this->currentRow = null;
		
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
        if (!$this->connecton->transRunning() || $force) {
            try {
                $this->connection->beginTransaction();
                $this->connection->setTransRunning(true);
                return true;
            }
            catch (PDOException $e) {
                try {
                    if ($force) {
                        $this->connection->commit();
                        $this->connection->beginTransaction();
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
			$this->connection->commit();
			$this->connection->setTransRunning(false);
			return true;
		} catch (PDOException $e) {
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
			$this->connection->rollBack();
			$this->connection->setTransRunning(false);
			return true;
		} catch (PDOException $e) {
        	return false;
		}
    }
    
	public function cleanEndTransaction($e) {
        $this->doRollBack($useTransaction);
        $this->connection->setTransRunning(false);

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
		$message   = 'Es trat ein Datenbank-Fehler auf: ';
		
		throw new sly_DB_PDO_Exception($message.'Fehlercode: '. $this->getErrno() .' '.$this->getError(), $this->getErrno());
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

    /**
     * Logging-Funktionalität
     *
     * Diese Methode verwendet den QueryLogger, der in einer gepatchten rex_sql-Version
     * enthalten ist, um die Anfragen des aktuellen Requests zu loggen. Sollte
     * rex_sql nicht gepatcht sein, wird nichts ausgeführt.
     *
     * @param string $query  die ausgeführt Anfrage
     * @param float  $time   die für die Query benötigte Zeit
     * @param int    $rows   die geholten Tupel (-1 = keine Angabe, -2 = Fehler)
     */
    private static function log($query, $time, $rows) {
        if (class_exists('_WV_QueryLogger')) {
            _WV_QueryLogger::log($query, $time, $rows);
        }
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
		
}
