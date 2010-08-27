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
abstract class sly_Form_Input_Base extends sly_Form_ElementBase implements sly_Form_IElement
{
	public function __construct($name, $label, $value, $id = null, $allowedAttributes = null)
	{
		if ($allowedAttributes === null) {
			$allowedAttributes = array('value', 'name', 'id', 'disabled', 'class', 'maxlength', 'readonly', 'style', 'type');
		}

		parent::__construct($name, $label, $value, $id, $allowedAttributes);
	}

	public function render()
	{
		$this->addClass('rex-form-text');

		// Prüfen, ob das Formular bereits abgeschickt und noch einmal angezeigt
		// werden soll. Falls ja, übernehmen wir den Wert aus den POST-Daten.

		$name = $this->attributes['name'];

		if (isset($_POST[$name]) && strlen($_POST[$name]) > 0) {
			$this->attributes['value'] = sly_post($name, 'string');
		}

		$attributeString = $this->getAttributeString();
		return '<input '.$attributeString.' />';
	}

	public function setMaxLength($maxLength)
	{
		$maxLength = abs(intval($maxLength));

		if ($maxLength > 0) {
			$this->setAttribute('maxlength', $maxLength);
		}
		else {
			$this->removeAttribute('maxlength');
		}
	}

	public function setReadOnly($readonly)
	{
		if ($readonly) {
			$this->setAttribute('readonly', 'readonly');
		}
		else {
			$this->removeAttribute('readonly');
		}
	}

	public function setSize($size)
	{
		$this->setAttribute('size', abs((int) $size));
	}
}
