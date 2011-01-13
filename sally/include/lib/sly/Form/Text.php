<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup form
 */
class sly_Form_Text extends sly_Form_ElementBase implements sly_Form_IElement {
	protected $content;
	protected $isHTML;

	public function __construct($label, $text, $id = null) {
		$id = $id === null ? 'a'.uniqid() : $id;
		parent::__construct('', $label, '', $id, array('class', 'style', 'id'));
		$this->content = $text;
		$this->isHTML  = false;
	}

	public function render() {
		$this->setAttribute('style', 'line-height:21px');
		$content = $this->isHTML ? $this->content : nl2br(sly_html($this->content));
		return '<span '.$this->getAttributeString().'>'.$content.'</span>';
	}

	public function setIsHTML($isHTML) {
		$this->isHTML = $isHTML ? true : false;
	}

	public function getOuterClass() {
		$this->addOuterClass('rex-form-read');
		return $this->outerClass;
	}

	public function getDisplayValue() {
		return md5($this->content);
	}
}
