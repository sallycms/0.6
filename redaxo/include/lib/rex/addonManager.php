<?php

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
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}
	
	protected function makeComponent($component)
	{
		return $component;
	}
}