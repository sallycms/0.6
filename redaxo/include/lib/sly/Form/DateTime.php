<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
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
