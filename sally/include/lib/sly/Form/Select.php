<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup form
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
		$name   = $this->label;
		$value  = $this->attributes['value'];
		$values = $this->values;
		$id     = $this->attributes['id'];

		switch ($this->style) {
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
