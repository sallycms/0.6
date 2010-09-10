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
class sly_Form_Freeform extends sly_Form_ElementBase implements sly_Form_IElement {
	protected $content;

	public function __construct($name, $label, $content, $id = null) {
		$allowed = array('name', 'class', 'id', 'style');
		parent::__construct($name, $label, '', $id, $allowed);
		$this->setContent($content);
	}

	public function setContent($content) {
		$this->content = $content;
	}

	public function render() {
		return $this->renderFilename('form/freeform.phtml');
	}
}
