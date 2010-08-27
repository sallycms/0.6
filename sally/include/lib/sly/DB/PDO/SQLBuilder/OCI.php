<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup database
 */
class sly_DB_PDO_SQLBuilder_OCI extends sly_DB_PDO_SQLBuilder{

	public function build_limit($sql, $offset = 0, $limit = -1)
	{
		$offset = intval($offset);
		$limit  = intval($limit);

		//http://www.oracle.com/technology/oramag/oracle/06-sep/o56asktom.html

		if($limit > 0 && $offset == 0){
			$sql = 'select * from ('.$sql.') where ROWNUM <= '.$limit;
		}elseif($limit < 0 && $offset > 0){
			$sql = 'select * from ('.$sql.') where ROWNUM > '.$offset;
		}else{
			$sql = 'select * from ( select /*+ FIRST_ROWS(n) */  a.*, ROWNUM rnum'
					.' from ('.$sql.') a where ROWNUM <= '.($limit + $offset).' )'
					.' where rnum  >= '.$offset;
		}

		return $sql;
	}

	public function build_list_tables()
	{
		// http://www.orafaq.com/forum/t/127009/0/
		return 'SELECT * FROM user_tables';
	}
}