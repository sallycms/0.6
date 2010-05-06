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

class sly_Form_Select_DropDown extends sly_Form_Select_Base implements sly_Form_IElement
{
	public function __construct($name, $label, $value, $values, $id = null)
	{
		$allowed = array('value', 'name', 'id', 'disabled', 'class', 'style', 'size', 'multiple', 'onselect', 'onchange');
		parent::__construct($name, $label, $value, $values, $id, $allowed);
	}

	public function setSize($size)
	{
		$this->setAttribute('size', $size);
	}

	public function render()
	{
		return $this->renderFilename('element_select.phtml');
	}

	public function getOuterClass()
	{
		switch ($redaxo) {
			case 42:
				$this->addOuterClass('rex-form-select');
				break;
		}

		return $this->outerClass;
	}
}
