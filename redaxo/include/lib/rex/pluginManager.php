<?php

/**
 * Manager zum Installieren von Plugins
 *
 * @deprecated
 */
class rex_pluginManager extends rex_baseManager
{
	private $addonName;

	/**
	 * Konstruktor
	 *
	 * @deprecated
	 *
	 * @param mixed $configArray  ungenutzt, nur zur REDAXO-Kompatibilität
	 */
	public function __construct($configArray, $addonName)
	{
		$this->addonName = $addonName;
		$this->service   = sly_Service_Factory::getService('Plugin');
	}

	/**
	 * Wandelt ein AddOn in ein Plugin eines anderen AddOns um
	 *
	 * @deprecated
	 *
	 * @param $addonName AddOn dem das Plugin eingefügt werden soll
	 * @param $pluginName Name des Plugins
	 * @param $includeFile Datei die eingebunden und umgewandelt werden soll
	 */
	public static function addon2plugin($addonName, $pluginName, $includeFile)
	{
		$service = sly_Service_Factory::getService('Plugin');
		return $service->mentalGymnasticsInclude($includeFile, array($addonName, $pluginName));
	}
	
	protected function makeComponent($component)
	{
		return array($this->addonName, $component);
	}
}
