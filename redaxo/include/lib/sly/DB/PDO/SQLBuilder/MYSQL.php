<?php

class sly_DB_PDO_SQLBuilder_MYSQL extends sly_DB_PDO_SQLBuilder
{
	public function build_limit($sql, $offset = 0, $limit = -1)
	{
		$offset = abs((int) $offset);
		$limit  = (int) $limit;
		$limit  = $limit < 0 ? '18446744073709551615' : $limit;
		
		return "$sql LIMIT $offset, $limit";
	}
}