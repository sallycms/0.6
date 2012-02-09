<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @since  0.6
 * @author zozi@webvariants.de
 */
class sly_Slice_Form extends sly_Form {
	public function __construct($action = '', $method = '', $title = '', $name = '', $id = '') {
		parent::__construct($action, $method, $title, $name, $id);
	}

	protected function addDataIndex($dataIndex) {
		foreach ($this->fieldsets as $fieldset) {
			foreach ($fieldset->getRows() as $row) {
				foreach ($row as $element) {
					if ($element instanceof sly_Form_ElementBase) {
						$name = $dataIndex.'['.$element->getName().']';
						$element->setAttribute('name', $name);
					}
				}
			}
		}
	}

	public function render($dataIndex = null) {
		if (is_string($dataIndex)) {
			$this->addDataIndex($dataIndex);
		}

		return parent::render(true);
	}
}
