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
class rex_form_radio_element extends rex_form_options_element
{
	// 1. Parameter nicht genutzt, muss aber hier stehen,
	// wg einheitlicher Konstrukturparameter
	public function __construct($tag = '', &$table, $attributes = array())
	{
		parent::__construct('', $table, $attributes);
	}

	public function formatLabel()
	{
		return '<span>'.$this->getLabel().'</span>';
	}

	public function formatElement()
	{
		$s       = '';
		$value   = $this->getValue();
		$options = $this->getOptions();
		$id      = $this->getAttribute('id');
		$attr    = array();

		foreach ($this->getAttributes() as $attributeName => $attributeValue) {
			if ($attributeName == 'id') continue;
			$attr[] = trim($attributeName).'="'.trim($attributeValue).'"';
		}

		$attr = implode(' ', $attr);

		foreach ($options as $opt_name => $opt_value) {
			$checked  = $opt_value == $value ? ' checked="checked"' : '';
			$opt_id   = $id.'_'.self::_normalizeId($opt_value);
			$opt_attr = $attr.' id="'.$opt_id.'"';

			$s .= '<input type="radio" value="'.sly_html($opt_value).'"'.$opt_attr.$checked.' />';
			$s .= '<label for="'.$opt_id.'">'.$opt_name.'</label>';
		}

		return $s;
	}
}
