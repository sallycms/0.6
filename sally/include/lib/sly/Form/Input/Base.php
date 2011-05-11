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
abstract class sly_Form_Input_Base extends sly_Form_ElementBase implements sly_Form_IElement {
	protected $annotation;

	public function __construct($name, $label, $value, $id = null) {
		parent::__construct($name, $label, $value, $id);
		$this->annotation = '';
	}

	public function render() {
		$this->addClass('rex-form-text');

		$this->attributes['value'] = $this->getDisplayValue();
		$attributeString = $this->getAttributeString();
		return '<input '.$attributeString.' />'.($this->annotation ? ' '.$this->annotation : '');
	}

	public function getDisplayValue() {
		return $this->getDisplayValueHelper('string', false);
	}

	public function setMaxLength($maxLength) {
		$maxLength = abs(intval($maxLength));

		if ($maxLength > 0) {
			$this->setAttribute('maxlength', $maxLength);
		}
		else {
			$this->removeAttribute('maxlength');
		}
	}

	public function setReadOnly($readonly) {
		if ($readonly) {
			$this->setAttribute('readonly', 'readonly');
		}
		else {
			$this->removeAttribute('readonly');
		}
	}

	public function setSize($size) {
		$this->setAttribute('size', abs((int) $size));
	}

	public function setAnnotation($annotation) {
		$this->annotation = trim($annotation);
	}
}
