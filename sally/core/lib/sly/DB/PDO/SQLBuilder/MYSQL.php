<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup database
 */
class sly_DB_PDO_SQLBuilder_MYSQL extends sly_DB_PDO_SQLBuilder {
	/**
	 * @param  string $sql
	 * @param  int    $offset
	 * @param  int    $limit
	 * @return string
	 */
	public function build_limit($sql, $offset = 0, $limit = -1) {
		$offset = abs((int) $offset);
		$limit  = $limit < 0 || $limit === null ? '18446744073709551615' : $limit;

		return "$sql LIMIT $offset, $limit";
	}

	/**
	 * @return string
	 */
	public function build_list_tables() {
		return 'SHOW TABLES';
	}
}
