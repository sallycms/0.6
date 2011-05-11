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
class sly_Form_Input_Radio extends sly_Form_Input_Base {
	protected $description;
	protected $checks;

	public function __construct($name, $label, $value, $description = 'ja', $id = null) {
		parent::__construct($name, $label, $value, $id);
		$this->description = $description;
		$this->checks      = array();
		$this->setAttribute('type', 'radio');
	}

	public function render() {
		$this->addClass('sly-form-radio');

		$this->attributes['checked'] = $this->getDisplayValue() ? 'checked' : '';
		$attributeString = $this->getAttributeString();

		return
			'<input '.$attributeString.' /> '.
			'<label class="sly-inline" for="'.$this->attributes['id'].'">'.sly_html($this->description).'</label>';
	}

	public function getOuterClass() {
		$this->addOuterClass('rex-form-radio');
		return $this->outerClass;
	}

	public function getDisplayValue() {
		$name = $this->getAttribute('name');
		return isset($_POST[$name]) ? $_POST[$name] : $this->getAttribute('checked') == 'checked';
	}

	public function setChecked($checked) {
		if ($checked) $this->setAttribute('checked', 'checked');
		else $this->removeAttribute('checked');
	}

	public function setChecks($checks) {
		$this->checks = sly_makeArray($checks);
	}

	public function getChecks() {
		return $this->checks;
	}
}
