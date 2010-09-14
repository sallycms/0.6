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
class sly_Form_Input_File extends sly_Form_Input_Base {
	public function __construct($name, $label, $id = null) {
		parent::__construct($name, $label, '', $id);
		$this->setAttribute('type', 'file');
	}

	public function getOuterClass() {
		$this->addOuterClass('rex-form-text');
		$this->addOuterClass('rex-form-file');
		return $this->outerClass;
	}
}
