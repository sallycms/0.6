<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * @ingroup redaxo
 */
class rex_form_checkbox_element extends rex_form_options_element
{
	// 1. Parameter nicht genutzt, muss aber hier stehen,
	// wg einheitlicher Konstrukturparameter
	public function __construct($tag = '', &$table, $attributes = array())
	{
		parent::__construct('', $table, $attributes);
		$this->setLabel(''); // Jede Checkbox bekommt eigenes Label
	}

	public function formatLabel()
	{
		// Da jedes Feld schon ein Label hat, hier nur eine "Überschrift" anbringen
		return '<span>'.$this->getLabel().'</span>';
	}

	public function formatElement()
	{
		$s       = '';
		$values  = explode('|+|', $this->getValue());
		$options = $this->getOptions();
		$name    = $this->getAttribute('name');
		$id      = $this->getAttribute('id');
		$attr    = array();

		foreach ($this->getAttributes() as $attributeName => $attributeValue) {
			if ($attributeName == 'name' || $attributeName == 'id') continue;
			$attr[] = trim($attributeName).'="'.trim($attributeValue).'"';
		}

		$attr = implode(' ', $attr);

		foreach ($options as $opt_name => $opt_value) {
			$checked  = in_array($opt_value, $values) ? ' checked="checked"' : '';
			$opt_id   = $id.'_'.self::_normalizeId($opt_value);
			$opt_attr = $attr.' id="'.$opt_id.'"';

			$s .= '<input type="checkbox" name="'.$name.'['.$opt_value.']" value="'.sly_html($opt_value).'"'.$opt_attr.$checked.' /> ';
			$s .= '<label for="'.$opt_id.'">'.$opt_name.'</label>';
		}

		return $s;
	}
}
