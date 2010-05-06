<?php
class sly_DB_PDO_SQLBuilder_PGSQL extends sly_DB_PDO_SQLBuilder{
	
	public function build_limit($sql, $offset, $limit)
	{
		return $sql . ' LIMIT ' . intval($limit) . ' OFFSET ' . intval($offset);
	}
	
}