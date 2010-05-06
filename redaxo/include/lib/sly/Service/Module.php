<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

/**
 * DB Model Klasse für Module
 * 
 * @author zozi@webvariants.de
 *
 */
class sly_Service_Module extends sly_Service_Base{

	protected $tablename = 'module';

	protected function makeObject(array $params){
		return new sly_Model_Module($params);
	}

}
