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
class sly_Form extends sly_Form_Base {
	protected $action;
	protected $method;
	protected $title;
	protected $name;
	protected $id;
	protected $enctype;
	protected $submitButton;
	protected $resetButton;
	protected $deleteButton;
	protected $applyButton;
	protected $fieldsets;
	protected $currentFieldset;
	protected $focussedElement;
	protected $buttonClasses;

	public function __construct($action, $method = 'POST', $title, $name = '', $id = '') {
		$this->action   = $action;
		$this->method   = strtoupper($method) == 'GET' ? 'GET' : 'POST';
		$this->title    = $title;
		$this->name     = $name;
		$this->id       = $id;
		$this->enctype  = false;

		$this->submitButton    = new sly_Form_Input_Button('submit', 'submit', 'Speichern');
		$this->resetButton     = new sly_Form_Input_Button('reset', 'reset', 'Zurücksetzen');
		$this->deleteButton    = null;
		$this->applyButton     = null;
		$this->hiddenValues    = array();
		$this->fieldsets       = array();
		$this->currentFieldset = null;
		$this->focussedElement = '';
		$this->buttonClasses   = array('submit' => array(), 'reset' => array(), 'delete' => array(), 'apply' => array());
	}

	public function setEncType($enctype) {
		$this->enctype = trim($enctype);
	}

	public function beginFieldset($title, $id = null, $columns = 1) {
		$this->currentFieldset = new sly_Form_Fieldset($title, $id, $columns);
		$this->fieldsets[]     = $this->currentFieldset;

		return $this->currentFieldset;
	}

	public function addRow(array $row) {
		if ($this->currentFieldset === null) {
			$this->beginFieldset($this->title);
		}

		$this->currentFieldset->addRow($row);
		return true;
	}

	public function addFieldset(sly_Form_Fieldset $fieldset) {
		$this->fieldsets[]     = $fieldset;
		$this->currentFieldset = null;
	}

	public function setSubmitButton($submitButton) {
		$this->submitButton = $submitButton instanceof sly_Form_Input_Button ? $submitButton : null;
	}

	public function setResetButton($resetButton) {
		$this->resetButton = $resetButton instanceof sly_Form_Input_Button ? $resetButton : null;
	}

	public function setApplyButton($applyButton) {
		$this->applyButton = $applyButton instanceof sly_Form_Input_Button ? $applyButton : null;
	}

	public function setDeleteButton($deleteButton) {
		$this->deleteButton = $deleteButton instanceof sly_Form_Input_Button ? $deleteButton : null;
	}

	public function getSubmitButton() { return $this->submitButton; }
	public function getResetButton()  { return $this->resetButton;  }
	public function getApplyButton()  { return $this->applyButton;  }
	public function getDeleteButton() { return $this->deleteButton; }

	public function addButtonClass($type, $class) {
		$this->buttonClasses[$type][] = trim($class);
		$this->buttonClasses[$type]   = array_unique($this->buttonClasses[$type]);
	}

	public function render($print = true) {
		$viewRoot = SLY_INCLUDE_PATH.'/views/_form/';

		if (!$print) ob_start();
		include $viewRoot.'/form.phtml';
		if (!$print) return ob_get_clean();
	}

	public function clearElements() {
		$this->fieldsets       = array();
		$this->currentFieldset = null;
	}

	public function setFocus($elementID) {
		$this->focussedElement = $elementID;
	}

	public function getCurrentFieldset() {
		return $this->currentFieldset;
	}
}
