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
class rex_form_widget_medialist_element extends rex_form_element
{
	// 1. Parameter nicht genutzt, muss aber hier stehen,
	// wg einheitlicher Konstrukturparameter
	public function __construct($tag = '', &$table, $attributes = array())
	{
		parent::__construct('', $table, $attributes);
	}

	public function formatElement()
	{
		static $widget_counter = 1;

		$html = rex_var_media::getMediaListButton($widget_counter, $this->getValue());
		$html = str_replace('MEDIALIST['.$widget_counter.']', $this->getAttribute('name').'[]', $html);

		$widget_counter++;
		return $html;
	}
}
