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

class sly_Form_MediaListButton extends sly_Form_Widget implements sly_Form_IElement
{
	public function __construct($name, $label, $value, $javascriptID = -1, $id = null, $allowedAttributes = null)
	{
		parent::__construct($name, $label, $value, $javascriptID, $id, $allowedAttributes, 'medialistbutton');
		$this->setAttribute('class', 'rex-form-select');
	}

	public function render()
	{
		// Prüfen, ob das Formular bereits abgeschickt und noch einmal angezeigt
		// werden soll. Falls ja, übernehmen wir den Wert aus den POST-Daten.

		$name = $this->attributes['name'];

		if (isset($_POST[$name]) && strlen($_POST[$name]) > 0) {
			$this->attributes['value'] = sly_postArray($name, 'string');
		}

		return $this->renderFilename('form/medialistbutton.phtml');
	}
}
