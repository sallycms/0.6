<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_DB_PDO_SQLBuilder_PGSQL extends sly_DB_PDO_SQLBuilder{
	
	public function build_limit($sql, $offset = 0, $limit = -1)
	{
		$limit = intval($limit);
		$limit = $limit > 0 ? $limit : 'ALL';
		return $sql . ' LIMIT ' . $limit . ' OFFSET ' . intval($offset);
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