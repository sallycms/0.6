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
abstract class sly_Form_Base {
	protected $hiddenValues;

	abstract public function addRow(array $row);
	abstract public function render($print = true);

	public function addElements($elements) {
		$success = true;
		foreach (array_filter($elements) as $element) {
			$success &= $this->addRow(array($element));
		}
		return $success;
	}

	public function addElement(sly_Form_IElement $element) {
		return $this->addRow(array($element));
	}

	public function add(sly_Form_IElement $element) {
		return $this->addRow(array($element));
	}

	public function addRows($rows) {
		$success = true;
		foreach (array_filter($rows) as $row) {
			$success &= $this->addRow($row);
		}
		return $success;
	}

	public function __toString() {
		return $this->render(false);
	}

	public function addHiddenValue($name, $value, $id = null) {
		$this->hiddenValues[$name] = array('value' => $value, 'id' => $id);
	}

	public function isMultilingual($row = null) {
		$rows = $row ? array($row) : $this->rows;

		foreach ($rows as $row) {
			foreach ($row as $element) {
				if ($element->isMultilingual()) return true;
			}
		}

		return false;
	}
}
