<?php
class DB_PDO_Helper_SQLITE implements DB_PDO_Helper{
	
	public function __construct(){}
	
	public function limit($sql, $offset, $limit)
	{
		$offset = intval($offset);
		$limit = intval($limit);
		return "$sql LIMIT $offset,$limit";
	}
	
}