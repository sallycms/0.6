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
class sly_Form_Input_Button extends sly_Form_Input_Base {
	public function __construct($type, $name, $value) {
		$allowed = array('value', 'name', 'id', 'disabled', 'class', 'type', 'style', 'onclick');
		parent::__construct($name, '', $value, null, $allowed);
		$this->setAttribute('type', in_array($type, array('button', 'reset', 'submit')) ? $type : 'button');
	}

	public function render() {
		$this->addClass('rex-form-text');
		$attributeString = $this->getAttributeString();
		return '<input '.$attributeString.' />';
	}
}
