<?php
class sly_DB_PDO_SQLBuilder_OCI extends sly_DB_PDO_SQLBuilder{
	
	public function build_limit($sql, $offset, $limit)
	{
		$offset = intval($offset);
		$stop = $offset + intval($limit);
		return 
			"SELECT * FROM (SELECT a.*, rownum ar_rnum__ FROM ($sql) a " .
			"WHERE rownum <= $stop) WHERE ar_rnum__ > $offset";
	}
	
}