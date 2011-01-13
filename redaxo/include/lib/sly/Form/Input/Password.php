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
