<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

abstract class sly_Form_Select_Base extends sly_Form_ElementBase
{
	protected $values;

	public function __construct($name, $label, $value, $values, $id = null, $allowedAttributes = null)
	{
		if ($allowedAttributes === null) {
			$allowedAttributes = array('value', 'name', 'id', 'disabled', 'class', 'style');
		}

		parent::__construct($name, $label, $value, $id, $allowedAttributes);
		$this->values = $values;

		if (is_array($value)) {
			$this->setMultiple(true);
		}
	}

	public function setMultiple($multiple)
	{
		if ($multiple) $this->setAttribute('multiple', 'multiple');
		else $this->removeAttribute('multiple');
	}

	public function setValues($values)
	{
		$this->values = $values;
	}
}
