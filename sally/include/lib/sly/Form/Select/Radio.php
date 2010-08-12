<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
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
