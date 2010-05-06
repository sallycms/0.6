<?php
interface sly_DB_Persistence extends Iterator{

	/**
	 * inserts a data set into the database
	 * 
	 * @param string $table
	 * @param array $values array('column' => $value,...) 
	 * 
	 * @return int affected rows
	 */
	public function insert($table, $values);
	
	/**
	 * updates data sets in database
	 * 
	 * @param string $table
	 * @param array $newValues array('column' => $value,...) 
	 * @param array $where array('column' => $value,...) 
	 */
	public function update($table, $newValues, $where = null);
	
	/**
	 * 
	 * @param string $table
	 * @param unknown_type $select
	 * @param unknown_type $where
	 * @param unknown_type $group
	 * @param unknown_type $order
	 * @param unknown_type $limit
	 * @param unknown_type $having
	 * @param unknown_type $joins
	 * 
	 * @return boolean
	 */
	public function select($table, $select = '*', $where = null, $group = null, $order = null, $limit = null, $having = null, $joins = null);
	
	/**
	 * 
	 * @param string $table
	 * @param array $where array('column' => $value,...) 
	 */
	public function delete($table, $where = null);
}