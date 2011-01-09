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
class sly_Service_Plugin extends sly_Service_AddOn_Base {
	/**
	 * Installiert ein Plugin
	 *
	 * @param array $plugin  Plugin als array(addon, plugin)
	 */
	public function install($plugin, $installDump = true) {
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
					$installError = t('plugin_no_install', $plugin, $e->getMessage());
				}

				if (!empty($installError)) {
					$state = t('plugin_no_install', $plugin).'<br />'.$installError;
				}
				else {
					if (is_readable($configFile)) {
						if (!$this->isActivated($plugin)) {
							$this->mentalGymnasticsInclude($configFile, $plugin);
						}
					}
					else {
						$state = t('plugin_config_not_found');
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
				$state = t('plugin_install_not_found');
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
		else {
			// store current plugin version
			$version = $this->getProperty($plugin, 'version', false);

			if ($version !== false) {
				sly_Util_Versions::set('plugins/'.implode('_', $plugin), $version);
			}
		}

		return $state;
	}

	/**
	 * De-installiert ein Plugin
	 *
	 * @param array $plugin  Plugin als array(addon, plugin)
	 */
	public function uninstall($plugin) {
		global $REX;
		list($addon, $pluginName) = $plugin;

		$pluginDir      = $this->baseFolder($plugin);
		$uninstallFile  = $pluginDir.'uninstall.inc.php';
		$uninstallSQL   = $pluginDir.'uninstall.sql';

		$state = $this->extend('PRE', 'UNINSTALL', $plugin, true);

		if (is_readable($uninstallFile)) {
			try {
				$this->mentalGymnasticsInclude($uninstallFile, $plugin);
			}
			catch (Exception $e) {
				$installError = t('plugin_no_uninstall', $plugin, $e->getMessage());
			}

			if (!empty($installError)) {
				$state = t('plugin_no_uninstall', $plugin).'<br />'.$installError;
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
			$state = t('plugin_uninstall_not_found');
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

	public function baseFolder($plugin) {
		list($addon, $pluginName) = $plugin;
		return rex_plugins_folder($addon, $pluginName).DIRECTORY_SEPARATOR;
	}

	protected function dynFolder($type, $plugin) {
		list($addon, $pluginName) = $plugin;

		$config = sly_Core::config();
		$s      = DIRECTORY_SEPARATOR;
		$dir    = SLY_DYNFOLDER.$s.$type.$s.$addon.$s.$pluginName;

		sly_Util_Directory::create($dir);
		return $dir;
	}

	protected function extend($time, $type, $plugin, $state) {
		list($addon, $pluginName) = $plugin;
		return rex_register_extension_point('SLY_PLUGIN_'.$time.'_'.$type, $state, array('addon' => $addon, 'plugin' => $pluginName));
	}

	/**
	 * Setzt eine Eigenschaft des Addons.
	 *
	 * @param  array  $plugin    Plugin als array(addon, plugin)
	 * @param  string $property  Name der Eigenschaft
	 * @param  mixed  $property  Wert der Eigenschaft
	 * @return mixed             der gesetzte Wert
	 */
	public function setProperty($plugin, $property, $value) {
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
	public function getProperty($plugin, $property, $default = null) {
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
	public function getRegisteredPlugins($addon) {
		$plugins = isset($this->data[$addon]['plugins']) ? array_keys($this->data[$addon]['plugins']) : array();
		natsort($plugins);
		return $plugins;
	}

	/**
	 * Gibt ein Array von verfügbaren Plugins zurück.
	 *
	 * Ein Plugin ist verfügbar, wenn es installiert und aktiviert ist.
	 *
	 * @return array  Array der verfügbaren Plugins
	 */
	public function getAvailablePlugins($addon) {
		$avail = array();

		foreach ($this->getRegisteredPlugins($addon) as $pluginName) {
			if ($this->isAvailable(array($addon, $pluginName))) {
				$avail[] = $pluginName;
			}
		}

		natsort($avail);
		return $avail;
	}

	/**
	 * Gibt ein Array aller installierten Plugins zurück.
	 *
	 * @param  string $addon  Name des AddOns
	 * @return array          Array aller registrierten Plugins
	 */
	public function getInstalledPlugins($addon) {
		$avail = array();

		foreach ($this->getRegisteredPlugins($addon) as $plugin) {
			if ($this->isInstalled(array($addon, $plugin))) $avail[] = $plugin;
		}

		natsort($avail);
		return $avail;
	}

	public function loadPlugin($plugin) {
		$this->loadConfig($plugin);
		$this->checkUpdate($plugin);

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
	public function mentalGymnasticsInclude($filename, $plugin) {
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

	protected function getI18NPrefix() {
		return 'addon_';
	}

	protected function getVersionKey($plugin) {
		return 'plugins/'.implode('_', $plugin);
	}
}
