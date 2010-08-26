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
