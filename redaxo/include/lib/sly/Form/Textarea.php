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

class sly_Form_Textarea extends sly_Form_Input_Base
{
	public function __construct($name, $label, $value, $id = null)
	{
		$allowed = array('value', 'name', 'id', 'disabled', 'class', 'maxlength', 'readonly', 'style', 'rows', 'cols', 'wrap');
		parent::__construct($name, $label, $value, $id, $allowed);
		$this->addClass('rex-form-textarea');
	}

	public function render()
	{
		return $this->renderFilename('form/textarea.phtml');
	}
}
