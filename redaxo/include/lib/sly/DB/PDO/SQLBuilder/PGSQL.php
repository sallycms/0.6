<?php
class sly_DB_PDO_SQLBuilder_PGSQL extends sly_DB_PDO_SQLBuilder{
	
	public function build_limit($sql, $offset, $limit)
	{
		return $sql . ' LIMIT ' . intval($limit) . ' OFFSET ' . intval($offset);
	}
	
	public function build_list_tables()
	{
		// http://bytes.com/topic/postgresql/answers/172978-sql-command-list-tables
		return 'SELECT c.relname FROM pg_catalog.pg_class c '.
			'LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace '.
			'WHERE c.relkind IN (\'r\',\'\') AND n.nspname NOT IN (\'pg_catalog\', \'pg_toast\') '.
			'AND pg_catalog.pg_table_is_visible(c.oid)';
	}
}