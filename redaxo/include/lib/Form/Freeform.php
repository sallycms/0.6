<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_Form_Freeform extends sly_Form_ElementBase implements sly_Form_IElement
{
	protected $content;
	
	public function __construct($name, $label, $content, $id = null)
	{
		$allowed = array('name', 'class', 'id', 'style');
		parent::__construct($name, $label, '', $id, $allowed);
		$this->setContent($content);
	}
	
	public function setContent($content)
	{
		$this->content = $content;
	}
	
	public function render($redaxo)
	{
		return $this->renderFilename($redaxo, 'element_freeform.phtml');
	}
}
