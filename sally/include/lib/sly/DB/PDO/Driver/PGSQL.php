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
class sly_DB_PDO_Driver_PGSQL extends sly_DB_PDO_Driver {
	public function getDSN() {
		$format = empty($this->database) ? 'pgsql:host=%s' : 'pgsql:host=%s;dbname=%s';
		return sprintf($format, $this->host, $this->database);
	}
}
