<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_Form extends sly_Form_Base
{
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
	
	public function __construct($action, $method = 'POST', $title, $name = '', $id = '')
	{
		global $REX;
		
		$this->action   = $action;
		$this->method   = strtoupper($method) == 'GET' ? 'GET' : 'POST';
		$this->title    = $title;
		$this->name     = $name;
		$this->id       = $id;
		$this->redaxo   = intval($REX['VERSION'].$REX['SUBVERSION']);
		$this->enctype  = false;
		
		$this->submitButton    = new sly_Form_Input_Button('submit', 'submit', 'Speichern');
		$this->resetButton     = new sly_Form_Input_Button('reset', 'reset', 'Zurücksetzen');
		$this->deleteButton    = null;
		$this->applyButton     = null;
		$this->hiddenValues    = array();
		$this->fieldsets       = array(array('title' => $title, 'elements' => array()));
		$this->currentFieldset = 0;
		$this->focussedElement = '';
		$this->buttonClasses   = array('submit' => array(), 'reset' => array(), 'delete' => array(), 'apply' => array());
	}
	
	public function setEncType($enctype)
	{
		$this->enctype = trim($enctype);
	}
	
	public function beginFieldset($title, $id = null)
	{
		$fieldset = array('title' => $title, 'elements' => array());
		
		if ($id !== null) {
			$fieldset['id'] = (string) $id;
		}
		
		$this->fieldsets[] = $fieldset;
		$this->currentFieldset++;
	}
	
	public function addElement(sly_Form_IElement $element)
	{
		foreach ($this->fieldsets[$this->currentFieldset]['elements'] as $e) {
			if ($e->getID() == $element->getID()) {
				return false;
			}
		}
		
		$this->fieldsets[$this->currentFieldset]['elements'][] = $element;
		return true;
	}
	
	public function setSubmitButton($submitButton)
	{
		$this->submitButton = $submitButton instanceof sly_Form_Input_Button ? $submitButton : null;
	}
	
	public function setResetButton($resetButton)
	{
		$this->resetButton = $resetButton instanceof sly_Form_Input_Button ? $resetButton : null;
	}
	
	public function setApplyButton($applyButton)
	{
		$this->applyButton = $applyButton instanceof sly_Form_Input_Button ? $applyButton : null;
	}
	
	public function setDeleteButton($deleteButton)
	{
		$this->deleteButton = $deleteButton instanceof sly_Form_Input_Button ? $deleteButton : null;
	}
	
	public function getSubmitButton() { return $this->submitButton; }
	public function getResetButton()  { return $this->resetButton;  }
	public function getApplyButton()  { return $this->applyButton;  }
	public function getDeleteButton() { return $this->deleteButton; }
	
	public function addButtonClass($type, $class)
	{
		$this->buttonClasses[$type][] = trim($class);
		$this->buttonClasses[$type]   = array_unique($this->buttonClasses[$type]);
	}
	
	public function render($print = true)
	{
		global $REX;
		
		$viewRoot = $REX['INCLUDE_PATH'].'/views/_form/';
		
		if (!$print) ob_start();
		include $viewRoot.'/form.phtml';
		if (!$print) return ob_get_clean();
	}
	
	public function clearElements()
	{
		$this->fieldsets       = array(array('title' => $title, 'elements' => array()));
		$this->currentFieldset = 0;
	}
	
	public function setFocus($elementID)
	{
		$this->focussedElement = $elementID;
	}
}
