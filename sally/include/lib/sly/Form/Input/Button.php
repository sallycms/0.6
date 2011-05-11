<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
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
		parent::__construct($name, '', $value, null);
		$this->setAttribute('type', in_array($type, array('button', 'reset', 'submit')) ? $type : 'button');
	}

	public function render() {
		$this->addClass('rex-form-text');
		$attributeString = $this->getAttributeString();
		return '<input '.$attributeString.' />';
	}
}
