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
 * DB Model Klasse für Slices
 * 
 * @author zozi@webvariants.de
 *
 */
class Service_Slice extends Service_Base{

	protected $tablename = 'slice';
	
	protected function makeObject(array $params){
		return new Model_Slice($params);
	}

}