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
 * A classic dropdown box
 *
 * This element will present the given list of values as a dropdown box.
 * This is the most common selection type and allows multiple or single selects,
 * using varying sizes. The wrapped value is always an array, even when the
 * box using single selection.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Select_DropDown extends sly_Form_Select_Base implements sly_Form_IElement {
	/**
	 * Sets the size of the dropdown box
	 *
	 * The size attribute is mainly useful when using a multi select dropdown
	 * box, as elements with a size > 1 only waste space when only one can be
	 * selected at a time.
	 *
	 * @param int $size  the new size
	 */
	public function setSize($size) {
		$this->setAttribute('size', (int) $size);
	}

	/**
	 * Enable or disable multi selects
	 *
	 * Use this method to toggle the "multiple" attribute.
	 *
	 * @param boolean $multiple  true to enable, false to disable multi selection
	 */
	public function setMultiple($multiple) {
		if ($multiple) $this->setAttribute('multiple', 'multiple');
		else $this->removeAttribute('multiple');
	}

	/**
	 * Renders the element
	 *
	 * This method renders the form element and returns its XHTML code.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		return $this->renderFilename('element/select/dropdown.phtml');
	}

	/**
	 * Returns the outer row class
	 *
	 * @return string  the outer class
	 */
	public function getOuterClass() {
		return $this->outerClass;
	}
}