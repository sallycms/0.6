<?php

/**
 * Klasse zum Prüfen ob Addons installiert/aktiviert sind
 *
 * @package redaxo4
 * @version svn:$Id$
 */
abstract class OOAddon extends rex_addon
{
	/**
	 * Prüft, ob ein System-Addon vorliegt
	 *
	 * @deprecated  sly_Service_AddOn benutzen
	 *
	 * @param  string $addon  Name des Addons
	 * @return boolean        true, wenn es sich um ein System-Addon handelt, sonst false
	 */
	public static function isSystemAddon($addon)
	{
		return sly_Service_Factory::getService('AddOn')->isSystemAddon($addon);
	}

	/**
	 * Gibt ein Array von verfügbaren Addons zurück.
	 *
	 * Ein Array ist verfügbar, wenn es installiert und aktiviert ist.
	 *
	 * @deprecated  sly_Service_AddOn benutzen
	 *
	 * @return array  Array der verfügbaren Addons
	 */
	public static function getAvailableAddons()
	{
		return sly_Service_Factory::getService('AddOn')->getAvailableAddons();
	}

	/**
	 * Gibt ein Array aller registrierten Addons zurück.
	 *
	 * Ein Addon ist registriert, wenn es dem System bekannt ist (addons.yaml).
	 *
	 * @deprecated  sly_Service_AddOn benutzen
	 *
	 * @return array  Array aller registrierten Addons
	 */
	public static function getRegisteredAddons()
	{
		return sly_Service_Factory::getService('AddOn')->getRegisteredAddons();
	}
}
