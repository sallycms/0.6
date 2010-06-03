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

class sly_Form_DateTime extends sly_Form_ElementBase implements sly_Form_IElement
{
	protected $withTime;
	
	public function __construct($name, $label, $value, $id = null, $allowedAttributes = null, $withTime = true)
	{
		if ($allowedAttributes === null) {
			$allowedAttributes = array('value', 'name', 'id', 'class', 'style');
		}

		parent::__construct($name, $label, $value, $id, $allowedAttributes);
		
		$this->withTime   = (boolean) $withTime;
		$this->outerClass = 'rex-form-text';
	}

	public function withTime()
	{
		return $this->withTime;
	}
	
	public function render($redaxo)
	{
		return $this->renderFilename($redaxo, 'element_datetime.phtml');
	}
}
