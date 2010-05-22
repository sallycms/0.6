<?php

/**
 * Managerklasse zum handeln von rexAddons
 *
 * @deprecated
 */
abstract class rex_baseManager
{
	protected $i18nPrefix;
	protected $service;

	/**
	 * Konstruktor
	 *
	 * @param $i18nPrefix Sprachprefix aller I18N Sprachschlüssel
	 */
	public function __construct($i18nPrefix)
	{
		$this->i18nPrefix = $i18nPrefix;
	}

	public function install($addonName, $installDump = true)
	{
		return $this->service->install($this->makeComponent($component), $installDump);
	}

	public function uninstall($component)
	{
		return $this->service->uninstall($this->makeComponent($component));
	}

	public function activate($component)
	{
		return $this->service->activate($this->makeComponent($component));
	}

	public function deactivate($component)
	{
		return $this->service->deactivate($this->makeComponent($component));
	}

	public function delete($component)
	{
		return $this->service->delete($this->makeComponent($component));
	}

	abstract protected function makeComponent($component);
}

/**
 * Manager zum Installieren von OOAddons
 *
 * @deprecated
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
		if(!self::$instance) self::$instance = new self($configArray);
		return self::$instance;
	}

	protected function makeComponent($component)
	{
		return $component;
	}
}

/**
 * Manager zum Installieren von OOPlugins
 *
 * @deprecated
 */
class rex_pluginManager extends rex_baseManager
{
	private $addonName;

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
	 * @param $addonName AddOn dem das PlugIn eingefügt werden soll
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
