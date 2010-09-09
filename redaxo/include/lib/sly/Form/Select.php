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

class sly_Form_Select extends sly_Form_Select_Base implements sly_Form_IElement
{
	protected $formElement;

	const STYLE_DROPDOWN = 0;
	const STYLE_RADIO    = 1;
	const STYLE_CHECKBOX = 2;

	public function __construct($name, $label, $value, $values, $id = null)
	{
		$allowed = array('value', 'name', 'id', 'disabled', 'class', 'style', 'size', 'multiple', 'onselect', 'onchange');
		parent::__construct($name, $label, $value, $values, $id, $allowed);
		$this->setStyle(self::STYLE_DROPDOWN);
	}

	public function setStyle($newStyle)
	{
		$name   = $this->attributes['name'];
		$label  = $this->label;
		$value  = $this->attributes['value'];
		$values = $this->values;
		$id     = $this->attributes['id'];

		switch ($newStyle) {
			case self::STYLE_CHECKBOX:
				$this->formElement = new sly_Form_Select_Checkbox($name, $label, $value, $values, $id);
				break;

			case self::STYLE_RADIO:
				$this->formElement = new sly_Form_Select_Radio($name, $label, $value, $values, $id);
				break;

			default:
				$this->formElement = new sly_Form_Select_DropDown($name, $label, $value, $values, $id);
				break;
		}

		return $this->formElement;
	}

	public function render()
	{
		return $this->formElement->render();
	}

	public function getOuterClass()
	{
		return $this->formElement->getOuterClass();
	}
}
