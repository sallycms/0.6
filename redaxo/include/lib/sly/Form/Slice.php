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

class sly_Form_Slice extends sly_Form_Base
{
	protected $elements;
	
	public function __construct()
	{
		global $REX;
		
		$this->hiddenValues = array();
		$this->redaxo       = intval($REX['VERSION'].$REX['SUBVERSION']);
		$this->elements     = array();
	}
	
	public function addElement(sly_Form_IElement $element)
	{
		foreach ($this->elements as $e) {
			if ($e->getID() == $element->getID()) {
				return false;
			}
		}
		
		$this->elements[] = $element;
		return true;
	}
	
	public function render($print = true)
	{
		global $REX;
		
		if (!$print) ob_start();
		include $REX['INCLUDE_PATH'].'/views/form/slice.phtml';
		if (!$print) return ob_get_clean();
	}
	
	public function clearElements()
	{
		$this->elements = array();
	}
	
	public function addButtonClass($type, $class)
	{
		// nichts tun...
	}
}
