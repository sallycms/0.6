<?php
class DB_PDO_Helper_PGSQL implements DB_PDO_Helper{
	
	public function __construct(){}
	
	public function limit($sql, $offset, $limit)
	{
		return $sql . ' LIMIT ' . intval($limit) . ' OFFSET ' . intval($offset);
	}
	
}