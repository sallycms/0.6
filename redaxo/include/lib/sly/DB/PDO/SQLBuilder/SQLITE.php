<?php
class sly_DB_PDO_SQLBuilder_SQLITE extends sly_DB_PDO_SQLBuilder{
	
	public function build_limit($sql, $offset = 0, $limit = -1)
	{
		$offset = intval($offset);
		$limit = intval($limit);
		return "$sql LIMIT $offset, $limit";
	}
	
	public function build_list_tables()
	{
		// http://www.sqlite.org/faq.html#q7
		return 'SELECT name FROM sqlite_master WHERE type = \'table\' ORDER BY name';
	}
}
