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
class sly_Form_Input_Password extends sly_Form_Input_Base {
	protected $redisplay;

	public function __construct($name, $label, $value = '', $id = null) {
		parent::__construct($name, $label, $value, $id);
		$this->setAttribute('type', 'password');
		$this->redisplay = false;
	}

	public function getOuterClass() {
		$this->addOuterClass('rex-form-text');
		return $this->outerClass;
	}

	public function setPasswordReDisplay($redisplay) {
		$this->redisplay = (boolean) $redisplay;
	}

	public function getDisplayValue() {
		if ($this->redisplay) {
			return parent::getDisplayValue();
		}

		return $this->attributes['value'];
	}
}
