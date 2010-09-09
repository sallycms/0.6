<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_Form_MediaButton extends sly_Form_ElementBase implements sly_Form_IElement
{
	protected $javascriptID;

	public function __construct($name, $label, $value, $javascriptID = -1, $id = null, $allowedAttributes = null)
	{
		if ($allowedAttributes === null) {
			$allowedAttributes = array('value', 'name', 'id', 'disabled', 'class', 'maxlength', 'readonly', 'style');
		}

		parent::__construct($name, $label, $value, $id, $allowedAttributes);
		$this->setAttribute('class', 'rex-form-text');

		$registry = sly_Core::getTempRegistry();

		if ($javascriptID <= 0) {
			$jsID = $registry->has('sly.form.mediabutton.jsid') ? ($registry->get('sly.form.mediabutton.jsid', 0) + 1) : 1;
		}
		else {
			$jsID = (int) $javascriptID;
		}

		$this->javascriptID = $jsID;
		$registry->set('sly.form.mediabutton.jsid', $jsID);
	}

	public function render()
	{
		// Prüfen, ob das Formular bereits abgeschickt und noch einmal angezeigt
		// werden soll. Falls ja, übernehmen wir den Wert aus den POST-Daten.

		$name = $this->attributes['name'];

		if (isset($_POST[$name]) && strlen($_POST[$name]) > 0) {
			$this->attributes['value'] = sly_post($name, 'string');
		}

		return $this->renderFilename('form/mediabutton.phtml');
	}
}
