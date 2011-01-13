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

/**
 * DB Model Klasse für Templates
 *
 * @author christoph@webvariants.de
 */
class sly_Service_Template extends sly_Service_Model_Base {
	protected $tablename = 'template';

	protected function makeObject(array $params) {
		return new sly_Model_Template($params);
	}
}
