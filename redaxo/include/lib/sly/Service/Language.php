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
 * @author christoph@webvariants.de
 */
class sly_Service_Language extends sly_Service_Base
{
	protected $tablename = 'clang';
	
	protected function makeObject(array $params)
	{
		return new sly_Language($params);
	}
	
	public function add($name)
	{
		return $this->create(array('name' => $name));
	}
}
