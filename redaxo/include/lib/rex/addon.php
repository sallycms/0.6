<?php

/**
 * Basisklasse für Addons/Plugins
 */
class rex_addon
{
	private static $instances;
	
	/**
	 * Konstruktor
	 */
	private function __construct()
	{
		/* deprecated + alle Methoden sind Proxies -> empty */
	}

	/**
	 * Erstellt ein rex-Addon aus dem Namespace $namespace.
	 *
	 * @deprecated  sly_Service_AddOn oder sly_Service_Plugin nutzen
	 *
	 * @param  string|array $namespace  Namespace des rex-Addons
	 * @return rex_addon                Zum Namespace erstellte rex-Addon Instanz
	 */
	public static function create($namespace)
	{
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Prüft ob das rex-Addon verfügbar ist, also installiert und aktiviert.
	 *
	 * @deprecated  sly_Service_AddOn oder sly_Service_Plugin nutzen
	 *
	 * @param  string|array $addon  Name des Addons oder array(addon, plugin) für ein Plugin
	 * @return boolean              true, wenn das rex-Addon verfügbar ist, sonst false
	 */
	public static function isAvailable($addon)
	{
		return self::isInstalled($addon) && self::isActivated($addon);
	}

	/**
	 * Prüft ob das rex-Addon aktiviert ist.
	 *
	 * @deprecated  sly_Service_AddOn oder sly_Service_Plugin nutzen
	 *
	 * @param  string|array $addon  Name des Addons oder array(addon, plugin) für ein Plugin
	 * @return boolean              true, wenn das rex-Addon aktiviert ist, sonst false
	 */
	public static function isActivated($addon)
	{
		return (boolean) self::getProperty($addon, 'status', false) == true;
	}

	/**
	 * Prüft ob das rex-Addon installiert ist.
	 *
	 * @deprecated  sly_Service_AddOn oder sly_Service_Plugin nutzen
	 *
	 * @param  string|array $addon  Name des Addons oder array(addon, plugin) für ein Plugin
	 * @return boolean              true, wenn das rex-Addon installiert ist, sonst false
	 */
	public static function isInstalled($addon)
	{
		return (boolean) self::getProperty($addon, 'install', false) == true;
	}

	/**
	 * Gibt die Version des rex-Addons zurück.
	 *
	 * @deprecated  sly_Service_AddOn oder sly_Service_Plugin nutzen
	 *
	 * @param  string|array $addon    Name des Addons oder array(addon, plugin) für ein Plugin
	 * @param  mixed        $default  Rückgabewert, falls keine Version gefunden wurde
	 * @return string                 Version des Addons
	 */
	public static function getVersion($addon, $default = null)
	{
		$service = sly_Service_Factory::getService(is_array($addon) ? 'Plugin' : 'AddOn');
		return $service->setProperty($addon, $property, $value);
	}

	/**
	 * Gibt den Autor des rex-Addons zur�ck.
	 *
	 * @deprecated  sly_Service_AddOn oder sly_Service_Plugin nutzen
	 *
	 * @param  string|array $addon    Name des Addons oder array(addon, plugin) für ein Plugin
	 * @param  mixed        $default  Rückgabewert, falls kein Autor gefunden wurde
	 * @return string                 Autor des Addons
	 */
	public static function getAuthor($addon, $default = null)
	{
		return self::getProperty($addon, 'author', $default);
	}

	/**
	 * Gibt die Support-Adresse des rex-Addons zur�ck.
	 *
	 * @deprecated  sly_Service_AddOn oder sly_Service_Plugin nutzen
	 *
	 * @param  string|array $addon    Name des Addons oder array(addon, plugin) für ein Plugin
	 * @param  mixed        $default  Rückgabewert, falls keine Support-Adresse gefunden wurde
	 * @return string                 Support-Adresse des Addons
	 */
	public static function getSupportPage($addon, $default = null)
	{
		return self::getProperty($addon, 'supportpage', $default);
	}

	/**
	 * Setzt eine Eigenschaft des rex-Addons.
	 *
	 * @deprecated  sly_Service_AddOn oder sly_Service_Plugin nutzen
	 *
	 * @param  string|array $addon     Name des Addons oder array(addon, plugin) für ein Plugin
	 * @param  string       $property  Name der Eigenschaft
	 * @param  mixed        $property  Wert der Eigenschaft
	 * @return mixed                   der gesetzte Wert
	 */
	public static function setProperty($addon, $property, $value)
	{
		$service = sly_Service_Factory::getService(is_array($addon) ? 'Plugin' : 'AddOn');
		return $service->setProperty($addon, $property, $value);
	}

	/**
	 * Gibt eine Eigenschaft des rex-Addons zur�ck.
	 *
	 * @deprecated  sly_Service_AddOn oder sly_Service_Plugin nutzen
	 *
	 * @param  string|array $addon     Name des Addons oder array(addon, plugin) für ein Plugin
	 * @param  string       $property  Name der Eigenschaft
	 * @param  mixed        $default   Rückgabewert, falls die Eigenschaft nicht gefunden wurde
	 * @return string                  Wert der Eigenschaft des Addons
	 */
	public static function getProperty($addon, $property, $default = null)
	{
		$service = sly_Service_Factory::getService(is_array($addon) ? 'Plugin' : 'AddOn');
		return $service->getProperty($addon, $property, $default);
	}
}