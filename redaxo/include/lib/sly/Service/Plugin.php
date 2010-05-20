<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

/**
 * @author christoph@webvariants.de
 */
class sly_Service_Plugin extends sly_Service_AddOn_Base
{
	public function __construct()
	{
		$this->data       = sly_Core::config()->get('ADDON/plugins');
		$this->i18nPrefix = 'plugin_';
	}
	
	/**
	 * Installiert ein Plugin
	 *
	 * @param array $plugin  Plugin als array(addon, plugin)
	 */
	public function install($plugin, $installDump = true)
	{
		global $REX;
		
		list ($addon, $pluginName) = $plugin;
		
		$pluginDir   = $this->baseFolder($plugin);
		$installFile = $pluginDir.'install.inc.php';
		$installSQL  = $pluginDir.'install.sql';
		$configFile  = $pluginDir.'config.inc.php';
		$filesDir    = $pluginDir.'files';
		$config      = sly_Core::config();
		
		$state = $this->extend('PRE', 'INSTALL', $plugin, true);

		// Prüfen des Plugin-Ornders auf Schreibrechte,
		// damit das Plugin später wieder gelöscht werden kann
		
		$state = rex_is_writable($pluginDir);

		if ($state) {
			if (is_readable($installFile)) {
				$this->mentalGymnasticsInclude($installFile, $plugin);

				$hasError = $config->has('ADDON/installmsg/'.$pluginName);

				if ($hasError || !$this->isInstalled($plugin)) {
					$state = $this->I18N('no_install', $pluginName).'<br />';
					
					if ($hasError) {
						$state .= $config->get('ADDON/installmsg/'.$pluginName);
					}
					else {
						$state .= $this->I18N('no_reason');
					}
				}
				else {
					if (is_readable($configFile)) {
						if (!$this->isActivated($plugin)) {
							$this->mentalGymnasticsInclude($configFile, $plugin);
						}
					}
					else {
						$state = $this->I18N('config_not_found');
					}

					if ($installDump && $state === true && is_readable($installSQL)) {
						$state = rex_install_dump($installSQL);

						if ($state !== true) {
							$state = 'Error found in install.sql:<br />'.$state;
						}
					}
					
					if ($state === true) {
						$state = $this->generateConfig();
					}
				}
			}
			else {
				$state = $this->I18N('install_not_found');
			}
		}
		
		$state = $this->extend('POST', 'INSTALL', $plugin, $state);
		
		// Dateien kopieren
		
		if ($state === true && is_dir($filesDir)) {
			if (!rex_copyDir($filesDir, $this->publicFolder($plugin), $REX['MEDIAFOLDER'])) {
				$state = $this->I18N('install_cant_copy_files');
			}
		}
		
		$state = $this->extend('POST', 'ASSET_COPY', $plugin, $state);

		if ($state !== true) {
			$this->setProperty($plugin, 'install', 0);
		}

		return $state;
	}
	
	/**
	 * De-installiert ein Plugin
	 *
	 * @param array $plugin  Plugin als array(addon, plugin)
	 */
	public function uninstall($plugin)
	{
		list($addon, $pluginName) = $plugin;
		
		$pluginDir      = $this->baseFolder($plugin);
		$uninstallFile  = $pluginDir.'uninstall.inc.php';
		$uninstallSQL   = $pluginDir.'uninstall.sql';
		$config         = sly_Core::config();
		
		$state = $this->extend('PRE', 'UNINSTALL', $plugin, true);

		if (is_readable($uninstallFile)) {
			$this->mentalGymnasticsInclude($uninstallFile, $plugin);
			
			$hasError = $config->has('ADDON/installmsg/'.$pluginName);

			if ($hasError || $this->isInstalled($plugin)) {
				$state = $this->I18N('no_uninstall', $pluginName).'<br />';
				
				if ($hasError) {
					$state .= $config->get('ADDON/installmsg/'.$pluginName);
				}
				else {
					$state .= $this->I18N('no_reason');
				}
			}
			else {
				$state = $this->deactivate($plugin);

				if ($state === true && is_readable($uninstallSQL)) {
					$state = rex_install_dump($uninstallSQL);

					if ($state !== true) {
						$state = 'Error found in uninstall.sql:<br />'.$state;
					}
				}

				if ($state === true) {
					$state = $this->generateConfig();
				}
			}
		}
		else {
			$state = $this->I18N('uninstall_not_found');
		}
		
		$state = $this->extend('POST', 'UNINSTALL', $plugin, $state);
		
		if ($state === true) $state = $this->deletePublicFiles($plugin);
		if ($state === true) $state = $this->deleteInternalFiles($plugin);
		
		$state = $this->extend('POST', 'ASSET_DELETE', $plugin, $state);

		if ($state !== true) {
			$this->setProperty($plugin, 'install', 1);
		}

		return $state;
	}

	/**
	 * Aktiviert ein Plugin
	 *
	 * @param array $plugin  Plugin als array(addon, plugin)
	 */
	public function activate($plugin)
	{
		if ($this->isActivated($plugin)) {
			return true;
		}
		
		if ($this->isInstalled($plugin)) {
			$state = $this->extend('PRE', 'ACTIVATE', $plugin, true);
			
			if ($state === true) {
				$this->setProperty($plugin, 'status', 1);
				$state = $this->generateConfig();
			}
		}
		else {
			$state = $this->I18N('no_activation', $plugin[1]);
		}

		return $this->extend('POST', 'ACTIVATE', $plugin, $state);
	}

	/**
	 * Deaktiviert ein Plugin
	 *
	 * @param array $plugin  Plugin als array(addon, plugin)
	 */
	public function deactivate($plugin)
	{
		if (!$this->isActivated($plugin)) {
			return true;
		}
		
		$state = $this->extend('PRE', 'DEACTIVATE', $plugin, true);
		
		if ($state === true) {
			$this->setProperty($plugin, 'status', 0);
			$state = $this->generateConfig();
		}

		return $this->extend('POST', 'DEACTIVATE', $plugin, $state);
	}

	/**
	 * Löscht ein Plugin
	 *
	 * @param array $plugin  Plugin als array(addon, plugin)
	 */
	public function delete($plugin)
	{
		$state = $this->extend('PRE', 'DELETE', $plugin, true);
		if ($state === true) $state = $this->deleteHelper($plugin);
		return $this->extend('POST', 'DELETE', $plugin, $state);
	}
	
	public function baseFolder($plugin)
	{
		list($addon, $pluginName) = $plugin;
		return rex_plugins_folder($addon, $pluginName);
	}

	public function publicFolder($plugin)
	{
		return $this->dynFolder('public', $plugin);
	}

	public function internalFolder($plugin)
	{
		return $this->dynFolder('internal', $plugin);
	}
	
	protected function dynFolder($type, $plugin)
	{
		list($addon, $pluginName) = $plugin;
		
		$config = sly_Core::config();
		$s      = DIRECTORY_SEPARATOR;
		$dir    = $config->get('DYNFOLDER').$s.$type.$s.$addon.$s.$pluginName;
		
		if (!is_dir($dir)) mkdir($dir, $config->get('DIRPERM'), true);
		return $dir;
	}
	
	protected function extend($time, $type, $plugin, $state)
	{
		list($addon, $pluginName) = $plugin;
		return rex_register_extension_point('SLY_PLUGIN_'.$time.'_'.$type, $state, array('addon' => $addon, 'plugin' => $pluginName));
	}
	
	public function deletePublicFiles($plugin)
	{
		return $this->deleteFiles('public', $plugin);
	}
	
	public function deleteInternalFiles($plugin)
	{
		return $this->deleteFiles('internal', $plugin);
	}
	
	protected function deleteFiles($type, $plugin)
	{
		$dir   = $this->dynFolder($type, $plugin);
		$state = $this->extend('PRE', 'DELETE_'.strtoupper($type), $plugin, true);
		
		if ($state !== true) {
			return $state;
		}
		
		if (is_dir($dir) && !rex_deleteDir($dir, true)) {
			return $this->I18N('install_cant_delete_files');
		}
		
		return $this->extend('POST', 'DELETE_'.strtoupper($type), $plugin, true);
	}
	
	protected function I18N()
	{
		global $I18N;

		$args    = func_get_args();
		$args[0] = $this->i18nPrefix.$args[0];

		return rex_call_func(array($I18N, 'msg'), $args, false);
	}

	public function generateConfig()
	{
		$plugins = array();
		
		foreach ($this->data as $addon => $list) {
			$plugins[$addon] = array_keys($list['install']);
		}
		
		return rex_generatePlugins($plugins);
	}
	
	public function isAvailable($plugin)
	{
		return $this->isInstalled($plugin) && $this->isActivated($plugin);
	}
	
	public function isInstalled($plugin)
	{
		return $this->getProperty($plugin, 'install', false) == true;
	}
	
	public function isActivated($plugin)
	{
		return $this->getProperty($plugin, 'status', false) == true;
	}
	
	public function getVersion($plugin, $default = null)
	{
		$version     = $this->getProperty($plugin, 'version', null);
		$versionFile = $this->baseFolder($plugin).'/version';
		
		if ($version === null && file_exists($versionFile)) {
			$version = file_get_contents($versionFile);
		}
		
		return $version === null ? $default : $version;
	}
	
	public function getAuthor($plugin, $default = null)
	{
		return $this->getProperty($plugin, 'author', $default);
	}
	
	public function getSupportPage($plugin, $default = null)
	{
		return $this->getProperty($plugin, 'supportpage', $default);
	}
	
	public function getIcon($plugin)
	{
		$directory = $this->publicFolder($plugin);
		$base      = $this->baseFolder($plugin);
		$icon      = $this->getProperty($plugin, 'icon', null);
		
		if ($icon === null) {
			list($addon, $plugin) = $plugin;
			
			if (file_exists($directory.'/images/icon.png')) {
				$icon = 'images/'.$addon.'/'.$plugin.'/icon.png';
			}
			elseif (file_exists($directory.'/images/icon.gif')) {
				$icon = 'images/'.$addon.'/'.$plugin.'/icon.gif';
			}
			elseif (file_exists($base.'/images/icon.png')) {
				$icon = $base.'/images/icon.png';
			}
			elseif (file_exists($base.'/images/icon.gif')) {
				$icon = $base.'/images/icon.gif';
			}
			else {
				$icon = false;
			}
		}
		
		return $icon;
	}
	
	/**
	 * Setzt eine Eigenschaft des Addons.
	 *
	 * @param  array  $plugin    Plugin als array(addon, plugin)
	 * @param  string $property  Name der Eigenschaft
	 * @param  mixed  $property  Wert der Eigenschaft
	 * @return mixed             der gesetzte Wert
	 */
	public function setProperty($plugin, $property, $value)
	{
		list($addon, $pluginName) = $plugin;
		
		if (!isset($this->data[$addon][$property])) {
			$this->data[$addon][$property] = array();
		}

		$this->data[$addon][$property][$pluginName] = $value;
		sly_Core::config()->set('ADDON/plugins', $this->data);
		return $value;
	}
	
	/**
	 * Gibt eine Eigenschaft des Plugins zurück.
	 *
	 * @param  array  $plugin     Plugin als array(addon, plugin)
	 * @param  string $property   Name der Eigenschaft
	 * @param  mixed  $default    Rückgabewert, falls die Eigenschaft nicht gefunden wurde
	 * @return string             Wert der Eigenschaft des Plugins
	 */
	public function getProperty($plugin, $property, $default = null)
	{
		list($addon, $pluginName) = $plugin;
		$this->data = sly_Core::config()->get('ADDON/plugins');
		return isset($this->data[$addon][$property][$pluginName]) ? $this->data[$addon][$property][$pluginName] : $default;
	}

	/**
	 * Gibt ein Array aller registrierten Plugins zurück.
	 *
	 * Ein Plugin ist registriert, wenn es dem System bekannt ist (plugins.yaml).
	 *
	 * @return array  Array aller registrierten Plugins
	 */
	public function getRegisteredPlugins($addon)
	{
		return isset($this->data[$addon]) ? array_keys($this->data[$addon]['install']) : array();
	}

	/**
	 * Gibt ein Array von verfügbaren Plugins zurück.
	 *
	 * Ein Plugin ist verfügbar, wenn es installiert und aktiviert ist.
	 *
	 * @return array  Array der verfügbaren Plugins
	 */
	public function getAvailablePlugins($addon)
	{
		$avail = array();
		
		foreach ($this->getRegisteredPlugins($addon) as $pluginName) {
			if ($this->isAvailable(array($addon, $pluginName))) {
				$avail[] = $pluginName;
			}
		}
		
		return $avail;
	}
	
	/**
	 * Gibt ein Array aller installierten Plugins zurück.
	 *
	 * @param  string $addon  Name des AddOns
	 * @return array          Array aller registrierten Plugins
	 */
	public function getInstalledPlugins($addon)
	{
		$avail = array();
		
		foreach ($this->getRegisteredPlugins($addon) as $plugin) {
			if ($this->isInstalled(array($addon, $plugin))) $avail[] = $plugin;
		}

		return $avail;
	}
	
	public function getConfig($plugin)
	{
		$configFile   = $this->baseFolder($plugin).'/config.yaml';
		$internalFile = $this->internalFolder($plugin).'/config.yaml';
		
		if (!file_exists($configFile) && !file_exists($internalFile)) {
			return null;
		}
		
		if (file_exists($configFile)) {
			$config = sly_Configuration::getInstance($configFile);
		}
		
		if (file_exists($internalFile)) {
			if ($config) $config->appendFile($internalFile);
			else $config = sly_Configuration::getInstance($internalFile);
		}
		
		return $config;
	}
	
	/**
	 * So sieht eine Methode aus, die sich auf ihr Refactoring freut.
	 *
	 * Gott sei Dank sind in Sally die AddOn-Daten im Service gekapselt, sodass
	 * die alten Install/Uninstall-Scripte problemlos nach $REX['ADDON']
	 * schreiben können.
	 */
	public function mentalGymnasticsInclude($filename, $plugin)
	{
		global $REX, $SLY, $I18N; // Nötig damit im Plugin verfügbar

		$ADDONSsic    = sly_Core::config()->get('ADDON');
		$REX['ADDON'] = array();
		$__TMP        = array('filename' => $filename, 'plugin' => $plugin);

		try {
			require $filename;

			$plugin       = $__TMP['plugin'];
			$pluginConfig = array();
			
			list($addonName, $pluginName) = $plugin;
			
			if (isset($ADDONSsic['plugins'][$addonName])) {
				$pluginConfig = $ADDONSsic['plugins'][$addonName];
			}

			if (isset($REX['ADDON']) && is_array($REX['ADDON'])) {
				foreach (array_keys($REX['ADDON']) as $key) {
					// alle Eigenschaften, die das Plugin betreffen, verschieben
					
					if (isset($REX['ADDON'][$key][$pluginName])) {
						$pluginConfig[$key][$pluginName] = $REX['ADDON'][$key][$pluginName];
						unset($REX['ADDON'][$key][$pluginName]);

						// ggf leeres Array löschen,
						// damit es beim Merge später nicht ein Vorhandenes überschreibt
						
						if (empty($REX['ADDON'][$key])) {
							unset($REX['ADDON'][$key]);
						}
					}
				}
			}

			// Addoneinstellungen als Plugindaten speichern
			$ADDONSsic['plugins'][$addonName] = $pluginConfig;
			// Alle überbleibenden Keys die ggf. andere Addons beinflussen einfließen lassen
			$REX['ADDON'] = array_merge_recursive($ADDONSsic, $REX['ADDON']);
		}
		catch (Exception $e) {
			$REX['ADDON']['installmsg'][$pluginName] =
				'Es ist eine unerwartete Ausnahme während der Installation aufgetreten: '.$e->getMessage();
		}
		
		// Synchronisation mit sly_Configuration
		sly_Core::config()->set('ADDON', $REX['ADDON']);
	}
}
