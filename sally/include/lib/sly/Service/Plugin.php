<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Plugin extends sly_Service_AddOn_Base
{
	public function __construct()
	{
		$this->data       = sly_Core::config()->get('ADDON');
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

		$state = $this->extend('PRE', 'INSTALL', $plugin, true);

		// check requirements

		if ($state) {
			if (!$this->isInstalled($plugin)) {
				$this->loadConfig($plugin);
			}

			$requires = $this->getProperty($plugin, 'requires');

			if (!empty($requires)) {
				$requires = sly_makeArray($requires);
				$aService = sly_Service_Factory::getAddOnService();

				foreach ($requires as $requiredAddon) {
					if (!$aService->isAvailable($requiredAddon)) {
						//TODO I18n
						return 'The addOn '.$requiredAddon.' is required to install this plugIn.';
					}
				}
			}

			$sallyVersions = $this->getProperty($plugin, 'sally');

			if (!empty($sallyVersions)) {
				$sallyVersions = sly_makeArray($sallyVersions);
				$versionOK     = false;

				foreach ($sallyVersions as $version) {
					$versionOK |= $this->checkVersion($version);
				}

				if (!$versionOK) {
					return 'This plugIn is not marked as compatible with your SallyCMS version ('.sly_Core::getVersion('X.Y.Z').').';
				}
			}
		}

		// Prüfen des Plugin-Ornders auf Schreibrechte,
		// damit das Plugin später wieder gelöscht werden kann

		if ($state) {
			if (is_readable($installFile)) {
				try {
					$this->mentalGymnasticsInclude($installFile, $plugin);
				}
				catch (Exception $e) {
					$installError = 'Es ist eine unerwartete Ausnahme während der Installation aufgetreten: '.$e->getMessage();
				}

				$hasError = !empty($installError);

				if ($hasError) {
					$state = $this->I18N('no_install', $pluginName).'<br />';

					if ($hasError) {
						$state .= $state .= $installError;
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
						$this->setProperty($plugin, 'install', true);
					}
				}
			}
			else {
				$state = $this->I18N('plugin_install_not_found');
			}
		}

		$state = $this->extend('POST', 'INSTALL', $plugin, $state);

		// Dateien kopieren

		if ($state === true && is_dir($filesDir)) {
			$state = $this->copyAssets($plugin);
		}

		$state = $this->extend('POST', 'ASSET_COPY', $plugin, $state);

		if ($state !== true) {
			$this->setProperty($plugin, 'install', false);
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

		$state = $this->extend('PRE', 'UNINSTALL', $plugin, true);

		if (is_readable($uninstallFile)) {
			$this->mentalGymnasticsInclude($uninstallFile, $plugin);

			$hasError = $REX['ADDON']['installmsg'][$pluginName];

			if ($hasError) {
				$state = $this->I18N('no_uninstall', $pluginName).'<br />';

				if ($hasError) {
					$state .= $REX['ADDON']['installmsg'][$pluginName];
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
					$this->setProperty($plugin, 'install', false);
				}
			}
		}
		else {
			$state = $this->I18N('plugin_uninstall_not_found');
		}

		$state = $this->extend('POST', 'UNINSTALL', $plugin, $state);

		if ($state === true) $state = $this->deletePublicFiles($plugin);
		if ($state === true) $state = $this->deleteInternalFiles($plugin);

		$state = $this->extend('POST', 'ASSET_DELETE', $plugin, $state);

		if ($state !== true) {
			$this->setProperty($plugin, 'install', true);
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
				$this->setProperty($plugin, 'status', true);
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
			$this->setProperty($plugin, 'status', false);
		}

		return $this->extend('POST', 'DEACTIVATE', $plugin, $state);
	}

	public function baseFolder($plugin)
	{
		list($addon, $pluginName) = $plugin;
		return rex_plugins_folder($addon, $pluginName).DIRECTORY_SEPARATOR;
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
		$dir    = SLY_DYNFOLDER.$s.$type.$s.$addon.$s.$pluginName;

		sly_Util_Directory::create($dir);
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

		return call_user_func(array($I18N, 'msg'), $args, false);
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

		return sly_Core::config()->set('ADDON/'.$addon.'/plugins/'.$pluginName.'/'.$property, $value);
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

		return sly_Core::config()->has('ADDON/'.$addon.'/plugins/'.$pluginName.'/'.$property) ? sly_Core::config()->get('ADDON/'.$addon.'/plugins/'.$pluginName.'/'.$property) : $default;
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
		return isset($this->data[$addon]['plugins']) ? array_keys($this->data[$addon]['plugins']) : array();
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

	public function loadPlugin($plugin) {
		$this->loadConfig($plugin);
		$pluginConfig = $this->baseFolder($plugin).'config.inc.php';

		if (file_exists($pluginConfig)) {
			$this->mentalGymnasticsInclude($pluginConfig, $plugin);
		}
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
		global $REX, $I18N; // Nötig damit im Plugin verfügbar

		// Sicherstellen, dass aktuelle Änderungen von Plugins/AddOns auch in
		// ADDONsic landen, da zwischenzeitlich keine Synchronisierung zwischen
		// $REX und sly_Configuration stattfindet.

		$ADDONSsic    = array_merge_recursive(sly_Core::config()->get('ADDON'), $REX['ADDON']);
		$REX['ADDON'] = array();
		$__TMP        = array('filename' => $filename, 'plugin' => $plugin);

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

}
