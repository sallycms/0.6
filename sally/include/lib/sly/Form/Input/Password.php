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
class sly_Form_Input_Password extends sly_Form_Input_Base
{
	public function __construct($name, $label, $value = '', $id = null)
	{
		parent::__construct($name, $label, $value, $id);
		$this->setAttribute('type', 'password');
	}

	public function getOuterClass()
	{
		$this->addOuterClass('rex-form-col-a');
		$this->addOuterClass('rex-form-text');
		return $this->outerClass;
	}

	public function render()
	{
		$this->addClass('rex-form-text');

		// Das Passwort-Eingabefeld besitzt eine eigene render()-Implementierung,
		// damit bei abgeschickte und erneut angezeigten Formularen eben NICHT
		// die übermittelten POST-Daten (= Passwörter) wieder eingesetzt werden.

		$attributeString = $this->getAttributeString();
		return '<input '.$attributeString.' />';
	}
}
