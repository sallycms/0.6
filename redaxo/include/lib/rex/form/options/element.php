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
class rex_form_options_element extends rex_form_element
{
	public $options;

	// 1. Parameter nicht genutzt, muss aber hier stehen,
	// wg einheitlicher Konstrukturparameter
	public function __construct($tag = '', &$table, $attributes = array())
	{
		parent::__construct($tag, $table, $attributes);
		$this->options = array();
	}

	public function addOption($name, $value)
	{
		$this->options[$name] = $value;
	}

	public function addOptions($options, $useOnlyValues = false)
	{
		if (!is_array($options) || empty($options)) {
			return false;
		}

		foreach ($options as $key => $option) {
			$option = (array) $option;

			if ($useOnlyValues) {
				$this->addOption($option[0], $option[0]);
			}
			else {
				if (!isset($option[1])) $option[1] = $key;
				$this->addOption($option[0], $option[1]);
			}
		}
	}

	public function addSqlOptions($qry)
	{
		$sql = new rex_sql();
		$this->addOptions($sql->getArray($qry, MYSQL_NUM));
	}

	public function addDBSqlOptions($qry)
	{
		$sql = new rex_sql();
		$this->addOptions($sql->getDBArray($qry, MYSQL_NUM));
	}

	public function getOptions()
	{
		return $this->options;
	}
}
