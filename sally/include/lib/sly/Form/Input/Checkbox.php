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
class sly_Form_Input_Checkbox extends sly_Form_Input_Base
{
	protected $description;

	public function __construct($name, $label, $value, $description = 'ja', $id = null)
	{
		$allowed = array('value', 'name', 'id', 'disabled', 'class', 'style', 'type', 'checked');
		parent::__construct($name, $label, $value, $id, $allowed);
		$this->description = $description;
		$this->setAttribute('type', 'checkbox');
	}

	public function render()
	{
		$this->addStyle('vertical-align:middle');

		$lineHeight      = 21;
		$attributeString = $this->getAttributeString();

		return
			'<input style="float: left;" '.$attributeString.' /> '.
			'<label class="inline" style="float: left;line-height:'.$lineHeight.'px" for="'.$this->attributes['id'].'">'.sly_html($this->description).'</label>';
	}

	public function setChecked($checked)
	{
		if ($checked) $this->setAttribute('checked', 'checked');
		else $this->removeAttribute('checked');
	}
}
