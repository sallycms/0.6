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
abstract class sly_Form_ElementBase {
	protected $label;
	protected $attributes;
	protected $allowed;
	protected $helpText;
	protected $outerClass;
	protected $formRowClass;
	protected $multilingual;

	public function __construct($name, $label, $value, $id = null, $allowedAttributes = null) {
		$this->attributes   = array();
		$this->label        = $label;
		$this->allowed      = $allowedAttributes;
		$this->outerClass   = '';
		$this->formRowClass = '';
		$this->multilingual = false;

		$this->setAttribute('name',  $name);
		$this->setAttribute('value', $value);
		$this->setAttribute('id',    $id === null ? $name : $id);
	}

	public function getID()    { return $this->getAttribute('id', '');    }
	public function getName()  { return $this->getAttribute('name', '');  }
	public function getValue() { return $this->getAttribute('value', ''); }
	public function getLabel() { return $this->label; }

	public function getAttribute($name, $default = null) {
		return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
	}

	public function setAttribute($name, $value) {
		if ($this->allowed === null || in_array($name, $this->allowed)) {
			$this->attributes[$name] = $value;
			return true;
		}

		return false;
	}

	public function removeAttribute($name) {
		unset($this->attributes[$name]);
	}

	public function addClass($className) {
		$class   = strval($this->getAttribute('class'));
		$classes = empty($class) ? array() : explode(' ', $class);

		if (!in_array($className, $classes)) {
			$classes[] = $className;
		}

		$this->setAttribute('class', implode(' ', array_unique($classes)));
	}

	public function addStyle($style) {
		$styles = strval($this->getAttribute('style'));
		$styles = empty($styles) ? array() : explode(';', $styles);

		if (!in_array($style, $styles)) {
			$styles[] = $style;
		}

		$this->setAttribute('style', implode(' ', array_unique($styles)));
	}

	protected function getAttributeString($exclude = array()) {
		if (!is_array($exclude)) $exclude = array($exclude);

		$attributes = array();
		foreach ($this->attributes as $name => &$value) {
			if (!is_array($value) && strlen($value) > 0 && !in_array($name, $exclude)) {
				$attributes[] = $name.'="'.sly_html($value).'"';
			}
		}
		return implode(' ', $attributes);
	}

	public function setDisabled($disabled) {
		if ($disabled) $this->setAttribute('disabled', 'disabled');
		else $this->removeAttribute('disabled');
	}

	public function setHelpText($helpText) {
		$this->helpText = $helpText;
	}

	public function setLabel($label) {
		$this->label = $label;
	}

	public function getHelpText() {
		return $this->helpText;
	}

	public function getOuterClass() {
		return $this->outerClass;
	}

	public function getFormRowClass() {
		return $this->formRowClass;
	}

	public function isContainer() {
		return false;
	}

	public function isMultilingual() {
		return $this->multilingual;
	}

	public function setMultilingual($multilingual = true) {
		$this->multilingual = (boolean) $multilingual;
		return $this->multilingual;
	}

	public function getDisplayValueHelper($type = 'string', $asArray = false) {
		// Prüfen, ob das Formular bereits abgeschickt und noch einmal angezeigt
		// werden soll. Falls ja, übernehmen wir den Wert aus den POST-Daten.

		$name = $this->attributes['name'];

		if (isset($_POST[$name]) && !$asArray) {
			return sly_post($name, $type);
		}

		if (isset($_POST[$name]) && $asArray && is_array($_POST[$name])) {
			return sly_postArray($name, $type);
		}

		return $this->attributes['value'];
	}

	public function getDisplayName() {
		return $this->getAttribute('name');
	}

	protected function renderFilename($filename) {
		ob_start();
		include SLY_INCLUDE_PATH.'/views/_form/'.$filename;
		return ob_get_clean();
	}

	public function addOuterClass($className) {
		$class   = strval($this->outerClass);
		$classes = empty($class) ? array() : explode(' ', $class);

		if (!in_array($className, $classes)) {
			$classes[] = $className;
		}

		$this->outerClass = implode(' ', array_unique($classes));
	}

	public function addFormRowClass($className) {
		$class   = strval($this->formRowClass);
		$classes = empty($class) ? array() : explode(' ', $class);

		if (!in_array($className, $classes)) {
			$classes[] = $className;
		}

		$this->formRowClass = implode(' ', array_unique($classes));
	}
}
