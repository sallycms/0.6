<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_DB_PDO_SQLBuilder_MYSQL extends sly_DB_PDO_SQLBuilder
{
	public function build_limit($sql, $offset = 0, $limit = -1)
	{
		$offset = abs((int) $offset);
		$limit  = (int) $limit;
		$limit  = $limit < 0 ? '18446744073709551615' : $limit;
		
		return "$sql LIMIT $offset, $limit";
	}
	
	public function build_list_tables()
	{
		return 'SHOW TABLES';
	}
}