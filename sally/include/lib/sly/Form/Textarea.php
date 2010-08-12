<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
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
