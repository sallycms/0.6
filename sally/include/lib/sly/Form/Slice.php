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
class sly_Form_Slice extends sly_Form_Base {
	protected $rows;

	public function __construct() {
		$this->hiddenValues = array();
		$this->rows         = array();
	}

	public function addRow(array $row) {
		$this->rows[] = $row;
		return true;
	}

	public function integrate(sly_Form $form, $containerAttrs = null, $fieldset = null) {
		$fieldset = $fieldset instanceof sly_Form_Fieldset ? $fieldset : $form->getCurrentFieldset();

		if ($containerAttrs !== null) {
			$attrs    = sly_makeArray($containerAttrs);
			$fragment = new sly_Form_Fragment();

			$attrs['class'] = isset($attrs['class']) ? $attrs['class'].' sly-slice' : 'sly-slice';

			$fragment->setContent('<div '.sly_Util_HTML::buildAttributeString($attrs).'>');
			$fieldset->addRow($fragment);
		}

		foreach ($this->hiddenValues as $key => $value) {
			$form->addHiddenValue($key, $value['value'], $value['id']);
		}

		foreach ($this->rows as $row) {
			$fieldset->addRow($row);
		}

		if ($containerAttrs !== null) {
			$fragment = new sly_Form_Fragment();
			$fragment->setContent('</div>');
			$fieldset->addRow($fragment);
		}
	}

	public function clear() {
		$this->rows = array();
	}

	public function render($print = true) {
		throw new sly_Exception('Can\' render a slice.');
	}
}
