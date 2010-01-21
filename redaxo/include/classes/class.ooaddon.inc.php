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
	 * @param string $addon Name des Addons
	 * @return boolean true, wenn es sich um ein System-Addon handelt, sonst false
	 */
	public static function isSystemAddon($addon)
	{
		global $REX;
		return in_array($addon, $REX['SYSTEM_ADDONS']);
	}

	/**
	 * Gibt ein Array von verfügbaren Addons zurück.
	 *
	 * @return array Array der verfügbaren Addons
	 */
	public static function getAvailableAddons()
	{
		$avail = array();
		foreach (self::getRegisteredAddons() as $addonName) {
			if (self::isAvailable($addonName)) $avail[] = $addonName;
		}

		return $avail;
	}

	/**
	 * Gibt ein Array aller registrierten Addons zurück.
	 * Ein Addon ist registriert, wenn es dem System bekannt ist (addons.inc.php).
	 *
	 * @return array Array aller registrierten Addons
	 */
	public static function getRegisteredAddons()
	{
		global $REX;

		$addons = array();
		
		if (isset($REX['ADDON']['install']) && is_array($REX['ADDON']['install'])) {
			$addons = array_keys($REX['ADDON']['install']);
		}

		return $addons;
	}
}
