<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_Form_Container extends sly_Form_ElementBase implements sly_Form_IElement
{
	protected $content;
	
	public function __construct($id = null, $class = '', $style = '')
	{
		$allowed = array('class', 'id', 'style');
		parent::__construct('', '', '', $id, $allowed);
		$this->setAttribute('class', $class);
		$this->setAttribute('style', $style);
	}
	
	public function setContent($content)
	{
		$this->content = $content;
	}
	
	public function render($redaxo)
	{
		return $this->renderFilename($redaxo, 'element_container.phtml');
	}
	
	public function isContainer()
	{
		return true;
	}
}
