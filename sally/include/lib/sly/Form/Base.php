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
abstract class sly_Form_Base
{
	protected $redaxo;
	protected $hiddenValues;

	abstract public function addElement(sly_Form_IElement $element);
	abstract public function render($version = false);

	public function addElements($elements)
	{
		$success = true;
		foreach ($elements as $element) {
			if ($element != null) {
				$success &= $this->addElement($element);
			}
		}
		return $success;
	}

	public function add(sly_Form_IElement $element)
	{
		return $this->addElement($element);
	}

	public function __toString()
	{
		return $this->render(false);
	}

	public function addHiddenValue($name, $value, $id = null)
	{
		$this->hiddenValues[$name] = array('value' => $value, 'id' => $id);
	}
}
