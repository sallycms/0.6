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
class sly_Form_ButtonBar extends sly_Form_ElementBase implements sly_Form_IElement {
	protected $buttons;

	public function __construct($buttons = array(), $id = null) {
		$id = $id === null ? 'a'.uniqid() : $id;
		parent::__construct('', '', '', $id, array('class', 'style', 'id'));
		$this->buttons = $buttons;
	}

	public function render() {
		return $this->renderFilename('form/buttonbar.phtml');
	}

	public function isContainer() {
		return true;
	}
}
