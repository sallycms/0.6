<?php
class sly_DB_PDO_SQLBuilder_MYSQL extends sly_DB_PDO_SQLBuilder{
	
	public function build_limit($sql, $limit, $offset){
		$offset = intval($offset);
		$limit = intval($limit);
		return "$sql LIMIT $offset,$limit";
	}
	
}