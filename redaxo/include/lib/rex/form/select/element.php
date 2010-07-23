<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * @package redaxo4
 */
class rex_form_select_element extends rex_form_element
{
	public $select;
	public $separator;

	// 1. Parameter nicht genutzt, muss aber hier stehen,
	// wg einheitlicher Konstrukturparameter
	function __construct($tag = '', &$table, $attributes = array())
	{
		parent::__construct('', $table, $attributes);

		$this->select = new rex_select();
		$this->setSeparator('|+|');
	}

	public function formatElement()
	{
		$multipleSelect = false;

		// Hier die Attribute des Elements an den Select weitergeben, damit diese angezeigt werden

		foreach($this->getAttributes() as $attributeName => $attributeValue) {
			if ($attributeName == 'multiple') $multipleSelect = true;
			$this->select->setAttribute($attributeName, $attributeValue);
		}

		if ($multipleSelect) {
			$this->setAttribute('name', $this->getAttribute('name').'[]');
			$selectedOptions = explode($this->separator, $this->getValue());

			if (is_array($selectedOptions) && !empty($selectedOptions[0])) {
				foreach ($selectedOptions as $selectedOption) {
					$this->select->setSelected($selectedOption);
				}
			}
		}
		else {
			$this->select->setSelected($this->getValue());
		}

		$this->select->setName($this->getAttribute('name'));
		return $this->select->get();
	}

	public function setSeparator($separator)
	{
		$this->separator = $separator;
	}

	public function getSelect()
	{
		return $this->select;
	}
}
