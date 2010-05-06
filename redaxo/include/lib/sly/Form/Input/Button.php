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

class sly_Form_Input_Button extends sly_Form_Input_Base
{
	public function __construct($type, $name, $value)
	{
		$allowed = array('value', 'name', 'id', 'disabled', 'class', 'type', 'style', 'onclick');
		parent::__construct($name, '', $value, null, $allowed);
		$this->setAttribute('type', in_array($type, array('button', 'reset', 'submit')) ? $type : 'button');
	}
}
