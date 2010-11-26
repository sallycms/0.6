<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Klasse zum Prüfen ob Plugins installiert/aktiviert sind
 *
 * @ingroup redaxo2
 * @deprecated
 */
class OOPlugin extends rex_addon
{
	/**
	 * @deprecated
	 */
	public static function isAvailable($addon, $plugin, $default = null)
	{
		return parent::isAvailable(array($addon, $plugin), $default);
	}

	/**
	 * @deprecated
	 */
	public static function isActivated($addon, $plugin, $default = null)
	{
		return parent::isActivated(array($addon, $plugin), $default);
	}

	/**
	 * @deprecated
	 */
	public static function isInstalled($addon, $plugin, $default = null)
	{
		return parent::isInstalled(array($addon, $plugin), $default);
	}

	/**
	 * @deprecated
	 */
	public static function getSupportPage($addon, $plugin, $default = null)
	{
		return parent::getSupportPage(array($addon, $plugin), $default);
	}

	/**
	 * @deprecated
	 */
	public static function getVersion($addon, $plugin, $default = null)
	{
		return parent::getVersion(array($addon, $plugin), $default);
	}

	/**
	 * @deprecated
	 */
	public static function getAuthor($addon, $plugin, $default = null)
	{
		return parent::getAuthor(array($addon, $plugin), $default);
	}

	/**
	 * @deprecated
	 */
	public static function getProperty($addon, $plugin, $property, $default = null)
	{
		return parent::getProperty(array($addon, $plugin), $property, $default);
	}

	/**
	 * @deprecated
	 */
	public static function setProperty($addon, $plugin, $property, $value)
	{
		return parent::setProperty(array($addon, $plugin), $property, $value);
	}

	/**
	 * Gibt ein Array aller verfügbaren Plugins zurück.
	 *
	 * @deprecated  sly_Service_Plugin benutzen
	 *
	 * @param  string $addon  Name des Addons
	 * @return array          Array aller verfügbaren Plugins
	 */
	public static function getAvailablePlugins($addon)
	{
		return sly_Service_Factory::getService('Plugin')->getAvailablePlugins($addon);
	}


	/**
	 * Gibt ein Array aller installierten Plugins zurück.
	 *
	 * @deprecated  sly_Service_Plugin benutzen
	 *
	 * @param  string $addon  Name des Addons
	 * @return array          Array aller registrierten Plugins
	 */
	public static function getInstalledPlugins($addon)
	{
		return sly_Service_Factory::getService('Plugin')->getInstalledPlugins($addon);
	}

	/**
	 * Gibt ein Array aller registrierten Plugins zurück.
	 *
	 * Ein Plugin ist registriert, wenn es dem System bekannt ist (plugins.yaml).
	 *
	 * @deprecated  sly_Service_Plugin benutzen
	 *
	 * @param  string $addon  Name des Addons
	 * @return array          Array aller registrierten Plugins
	 */
	public static function getRegisteredPlugins($addon)
	{
		return sly_Service_Factory::getService('Plugin')->getRegisteredPlugins($addon);
	}
}
