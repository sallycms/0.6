<?php
class sly_DB_PDO_SQLBuilder_SQLITE extends sly_DB_PDO_SQLBuilder{
	
	public function build_limit($sql, $offset, $limit)
	{
		$offset = intval($offset);
		$limit = intval($limit);
		return "$sql LIMIT $offset,$limit";
	}
	
}