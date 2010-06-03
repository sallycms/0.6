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
 * @author christoph@webvariants.de
 */
class sly_Model_Language extends sly_Model_Base
{
	protected $name        = '';
	protected $_attributes = array('name' => 'string');
	
	public function getName()      { return $this->name;        }
	public function setName($name) { $this->name = trim($name); }
}
