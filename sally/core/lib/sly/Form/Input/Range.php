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
 * HTML5 input field for numbers (with slider)
 *
 * This element will be rendered as a slider by browsers with HTML5 support,
 * otherwise as a slider using jQuery UI.
 *
 * @ingroup form
 * @author  Christoph
 * @since   0.5
 */
class sly_Form_Input_Range extends sly_Form_Input_Number {
	/**
	 * Constructor
	 *
	 * @param string $name    element name
	 * @param string $label   the label
	 * @param array  $value   the current text
	 * @param string $id      optional ID (if not given, the name is used)
	 */
	public function __construct($name, $label, $value = '', $id = null) {
		parent::__construct($name, $label, $value, $id);
		$this->setAttribute('type', 'range');
		$this->addClass('rex-form-text');
	}

	/**
	 * Renders the element
	 *
	 * This method renders the element.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		$path   = 'assets/js/';
		$layout = sly_Core::getLayout();

		$layout->addCSSFile($path.'jqueryui-theme/jqueryui.min.css');
		$layout->addJavaScriptFile($path.'jqueryui.core.min.js');
		$layout->addJavaScriptFile($path.'jqueryui.widget.min.js');
		$layout->addJavaScriptFile($path.'jqueryui.mouse.min.js');
		$layout->addJavaScriptFile($path.'jqueryui.slider.min.js');

		$this->attributes['min']  = $this->min;
		$this->attributes['max']  = $this->max;
		$this->attributes['step'] = $this->step;

		return parent::render();
	}
}
