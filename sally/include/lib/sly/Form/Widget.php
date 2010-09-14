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

/**
 * @ingroup form
 */
abstract class sly_Form_Widget extends sly_Form_ElementBase {
	protected $javascriptID;
	protected $namespace;

	public function __construct($name, $label, $value, $javascriptID, $id, $allowedAttributes, $namespace) {
		parent::__construct($name, $label, $value, $id, $allowedAttributes);
		$this->namespace = 'sly.form.widget.'.$namespace;
		$this->setJavaScriptID($javascriptID);
	}

	public function setJavaScriptID($javascriptID) {
		$registry = sly_Core::getTempRegistry();
		$key      = $this->namespace.'.jsid';

		if ($javascriptID <= 0) {
			$jsID = $registry->has($key) ? ($registry->get($key) + 1) : 1;
		}
		else {
			$jsID = (int) $javascriptID;
		}

		$this->javascriptID = $jsID;
		$registry->set($key, $jsID);

		return $jsID;
	}
}
