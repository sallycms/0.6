<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
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
