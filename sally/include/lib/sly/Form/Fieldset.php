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
class sly_Form_Fieldset {
	protected $rows;
	protected $num;
	protected $columns;
	protected $legend;
	protected $id;

	public function __construct($legend, $id = '', $columns = 1, $num = -1) {
		$this->rows    = array();
		$this->columns = $columns;
		$this->legend  = $legend;
		$this->id      = $id;

		$this->setNum($num);
	}

	public function addRow($row) {
		$this->rows[] = sly_makeArray($row);
		return true;
	}

	public function addRows($rows) {
		$success = true;
		foreach (array_filter($rows) as $row) {
			$success &= $this->addRow($row);
		}
		return $success;
	}

	public function render($print = true) {
		global $REX;

		if (!$print) ob_start();
		include SLY_INCLUDE_PATH.'/views/_form/fieldset.phtml';
		if (!$print) return ob_get_clean();
	}

	public function clearElements() {
		$this->elements = array();
	}

	public function getNum() {
		return $this->num;
	}

	public function getColumns() {
		return $this->columns;
	}

	public function getLegend() {
		return $this->legend;
	}

	public function getID($id) {
		return $this->id;
	}

	public function setColumns($num) {
		$this->columns = ($num > 0 && $num < 26) ? $num : 1;
		return $this->columns;
	}

	public function setLegend($legend) {
		$this->legend = trim($legend);
		return $this->legend;
	}

	public function setID($id) {
		$this->id = trim($id);
		return $this->id;
	}

	public function setNum($num) {
		$registry = sly_Core::getTempRegistry();
		$key      = 'sly.form.fieldset.num';

		if ($num <= 0) {
			$num = $registry->has($key) ? ($registry->get($key) + 1) : 1;
		}
		else {
			$num = (int) $num;
		}

		$this->num = $num;
		$registry->set($key, $num);

		return $num;
	}
}
