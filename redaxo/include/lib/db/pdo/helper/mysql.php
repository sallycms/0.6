<?php
class DB_PDO_Helper_MYSQL implements DB_PDO_Helper{
	
	public function __construct(){}

	public function limit($sql, $limit, $offset){
		$offset = intval($offset);
		$limit = intval($limit);
		return "$sql LIMIT $offset,$limit";
	}
	
	
}