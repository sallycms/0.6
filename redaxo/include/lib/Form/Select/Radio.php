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

class sly_Form_Select_Radio extends sly_Form_Select_Base implements sly_Form_IElement
{
	public function __construct($name, $label, $value, $values, $id = null)
	{
		$allowed = array('value', 'name', 'id', 'disabled', 'class', 'style');
		parent::__construct($name, $label, $value, $values, $id, $allowed);
	}

	public function render()
	{
		return $this->renderFilename('form/select/radio.phtml');
	}
}
