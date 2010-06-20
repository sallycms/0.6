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

/**
 * DB Model Klasse für Medienkategorien
 *
 * @author christoph@webvariants.de
 */
class sly_Service_Media_Category extends sly_Service_Model_Base {
	protected $tablename = 'file_category';

	protected function makeObject(array $params) {
		return new sly_Model_Media_Category($params);
	}
}
