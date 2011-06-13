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
 * Linklist button
 *
 * This element will render a special widget that allows the user to select
 * a list of articles. The articles will be returned without any language
 * information, so only their IDs are returned.
 * Currently, only filtering the articles by name is supported. The linkmap
 * popup is not used yet.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Widget_LinkListButton extends sly_Form_ElementBase implements sly_Form_IElement {
	/**
	 * Constructor
	 *
	 * @param string $name   the element name
	 * @param string $label  the label
	 * @param mixed  $value  the current value (array or comma seperated string of IDs)
	 * @param string $id     optional HTML ID
	 */
	public function __construct($name, $label, $value, $id = null) {
		parent::__construct($name, $label, $value, $id);
		$this->setAttribute('class', 'rex-form-select');
	}

	/**
	 * Render the element
	 *
	 * @return string  the rendered XHTML
	 */
	public function render() {
		$this->attributes['value'] = $this->getDisplayValue();
		return $this->renderFilename('form/linklistbutton.phtml');
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @return array  list of article IDs
	 */
	public function getDisplayValue() {
		$ids = $this->getDisplayValueHelper('string', false);
		if ($ids === null) return array();
		return is_array($ids) ? $ids : explode(',', $ids);
	}
}
