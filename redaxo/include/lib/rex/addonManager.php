<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Manager zum Installieren von OOAddons
 *
 * @deprecated
 * @package redaxo4
 */
class rex_addonManager extends rex_baseManager
{
	private static $instance;

	public function __construct($configArray = null)
	{
		$this->service = sly_Service_Factory::getService('AddOn');
	}

	/**
	 * @param  $configArray      ungenutzt, nur zur REDAXO-Kompatibilität
	 * @return rex_addonManager  Singleton
	 */
	public static function getInstance($configArray = null)
	{
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	protected function makeComponent($component)
	{
		return $component;
	}
}
