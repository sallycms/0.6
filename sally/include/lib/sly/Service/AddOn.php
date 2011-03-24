<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
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
class sly_Service_AddOn extends sly_Service_AddOn_Base {
	protected static $addonsLoaded = array();

	public function install($addonName, $installDump = true) {
		$addonDir    = $this->baseFolder($addonName);
		$installFile = $addonDir.'install.inc.php';
		$installSQL  = $addonDir.'install.sql';
		$configFile  = $addonDir.'config.inc.php';

		// return error message if an addOn wants to stop the install process

		$state = $this->extend('PRE', 'INSTALL', $addonName, true);

		if ($state !== true) {
			return $state;
		}

		// check for config.inc.php before we do anything

		if (!is_readable($configFile)) {
			return t('config_not_found');
		}

		// check requirements

		if (!$this->isAvailable($addonName)) {
			$this->loadConfig($addonName); // static.yml, defaults.yml
		}

		$requires = sly_makeArray($this->getProperty($addonName, 'requires'));

		foreach ($requires as $requiredAddon) {
			if (!$this->isAvailable($requiredAddon)) {
				return t('addon_addon_required', $requiredAddon, $addonName);
			}
		}

		// check Sally version

		$sallyVersions = $this->getProperty($addonName, 'sally');

		if (!empty($sallyVersions)) {
			$sallyVersions = sly_makeArray($sallyVersions);
			$versionOK     = false;

			foreach ($sallyVersions as $version) {
				$versionOK |= $this->checkVersion($version);
			}

			if (!$versionOK) {
				return t('addon_sally_incompatible', sly_Core::getVersion('X.Y.Z'));
			}
		}
		else {
			return t('addon_has_no_sally_version_info');
		}

		// include install.inc.php if available

		if (is_readable($installFile)) {
			try {
				$this->req($installFile);
			}
			catch (Exception $e) {
				return t('addon_no_install', $addonName, $e->getMessage());
			}
		}

		// read install.sql and install DB

		if ($installDump && is_readable($installSQL)) {
			$state = rex_install_dump($installSQL);

			if ($state !== true) {
				return 'Error found in install.sql:<br />'.$state;
			}
		}

		// copy assets to data/dyn/public

		if (is_dir($addonDir.'assets')) {
			$this->copyAssets($addonName);
		}

		// mark addOn as installed
		$this->setProperty($addonName, 'install', true);

		// store current addOn version
		$version = $this->getProperty($addonName, 'version', false);

		if ($version !== false) {
			sly_Util_Versions::set('addons/'.$addonName, $version);
		}

		// notify listeners
		return $this->extend('POST', 'INSTALL', $addonName, true);
	}

	/**
	 * De-installiert ein Addon
	 *
	 * @param $addonName Name des Addons
	 */
	public function uninstall($addonName) {
		$addonDir      = $this->baseFolder($addonName);
		$uninstallFile = $addonDir.'uninstall.inc.php';
		$uninstallSQL  = $addonDir.'uninstall.sql';
		$config        = sly_Core::config();

		// if not installed, try to disable if needed

		if (!$this->isInstalled($addonName)) {
			return $this->deactivate($addonName);
		}

		// check for dependencies

		if ($this->isActivated($addonName)) {
			$dependencies = $this->getDependencies($addonName, true);

			if (!empty($dependencies)) {
				$dep = reset($dependencies);
				$msg = is_array($dep) ? 'addon_plugin_required' : 'addon_addon_required';
				return t($msg, $addonName, is_array($dep) ? reset($dep).'/'.end($dep) : $dep);
			}
		}

		// stop if addOn forbids uninstall

		$state = $this->extend('PRE', 'UNINSTALL', $addonName, true);

		if ($state !== true) {
			return $state;
		}

		// deactivate addOn first

		$state = $this->deactivate($addonName);

		if ($state !== true) {
			return $state;
		}

		// include uninstall.inc.php if available

		if (is_readable($uninstallFile)) {
			try {
				$this->req($uninstallFile);
			}
			catch (Exception $e) {
				return t('addon_no_uninstall', $addonName, $e->getMessage());
			}
		}

		// read uninstall.sql

		if (is_readable($uninstallSQL)) {
			$state = rex_install_dump($uninstallSQL);

			if ($state !== true) {
				return 'Error found in uninstall.sql:<br />'.$state;
			}
		}

		// mark addOn as not installed
		$this->setProperty($addonName, 'install', false);

		// delete files
		$state  = $this->deletePublicFiles($addonName);
		$stateB = $this->deleteInternalFiles($addonName);

		if ($stateB !== true) {
			// overwrite or concat stati
			$state = $state === true ? $stateB : $stateA.'<br />'.$stateB;
		}

		// notify listeners
		return $this->extend('POST', 'UNINSTALL', $addonName, $state);
	}

	public function baseFolder($addonName) {
		$dir = SLY_ADDONFOLDER.DIRECTORY_SEPARATOR;
		if (!empty($addonName)) $dir .= $addonName.DIRECTORY_SEPARATOR;
		return $dir;
	}

	protected function dynFolder($type, $addonName) {
		$config = sly_Core::config();
		$dir    = SLY_DYNFOLDER.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.$addonName;

		sly_Util_Directory::create($dir);
		return $dir;
	}

	protected function dynPath($type, $addonName) {
		$config = sly_Core::config();
		$dir    = SLY_BASE.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.$addonName;

		sly_Util_Directory::create($dir);
		return $dir;
	}

	protected function extend($time, $type, $addonName, $state) {
		return rex_register_extension_point('SLY_ADDON_'.$time.'_'.$type, $state, array('addon' => $addonName));
	}

	/**
	 * Setzt eine Eigenschaft des Addons.
	 *
	 * @param  string $addon     Name des Addons
	 * @param  string $property  Name der Eigenschaft
	 * @param  mixed  $property  Wert der Eigenschaft
	 * @return mixed             der gesetzte Wert
	 */
	public function setProperty($addonName, $property, $value) {
		return sly_Core::config()->set('ADDON/'.$addonName.'/'.$property, $value);
	}

	/**
	 * Gibt eine Eigenschaft des AddOns zurück.
	 *
	 * @param  string $addonName  Name des Addons
	 * @param  string $property   Name der Eigenschaft
	 * @param  mixed  $default    Rückgabewert, falls die Eigenschaft nicht gefunden wurde
	 * @return string             Wert der Eigenschaft des Addons
	 */
	public function getProperty($addonName, $property, $default = null) {
		return sly_Core::config()->has('ADDON/'.$addonName.'/'.$property) ? sly_Core::config()->get('ADDON/'.$addonName.'/'.$property) : $default;
	}

	/**
	 * Gibt ein Array aller registrierten Addons zurück.
	 *
	 * Ein Addon ist registriert, wenn es dem System bekannt ist (addons.yaml).
	 *
	 * @return array  Array aller registrierten Addons
	 */
	public function getRegisteredAddons() {
		$data = sly_Core::config()->get('ADDON');
		$data = !empty($data) ? array_keys($data) : array();
		natsort($data);
		return $data;
	}

	/**
	 * Gibt ein Array von verfügbaren Addons zurück.
	 *
	 * Ein Addon ist verfügbar, wenn es installiert und aktiviert ist.
	 *
	 * @return array  Array der verfügbaren Addons
	 */
	public function getAvailableAddons() {
		$avail = array();

		foreach ($this->getRegisteredAddons() as $addonName) {
			if ($this->isAvailable($addonName)) $avail[] = $addonName;
		}

		natsort($avail);
		return $avail;
	}

	/**
	 * Prüft, ob ein System-Addon vorliegt
	 *
	 * @deprecated  Since v0.3 there are no system addOns anymore.
	 *
	 * @param  string $addonName  Name des Addons
	 * @return boolean            true, wenn es sich um ein System-Addon handelt, sonst false
	 */
	public function isSystemAddon($addonName) {
		return false;
	}

	public function loadAddon($addonName) {
		if (in_array($addonName, self::$addonsLoaded)) return true;

		$this->loadConfig($addonName);

		$requires = $this->getProperty($addonName, 'requires');

		if (!empty($requires)) {
			if (!is_array($requires)) $requires = sly_makeArray($requires);

			foreach ($requires as $requiredAddon) {
				$this->loadAddon($requiredAddon);
			}
		}

		$this->checkUpdate($addonName);

		$addonConfig = $this->baseFolder($addonName).'config.inc.php';

		if (file_exists($addonConfig)) {
			$this->req($addonConfig);
		}

		self::$addonsLoaded[] = $addonName;
	}

	protected function getI18NPrefix() {
		return 'addon_';
	}

	protected function getVersionKey($addon) {
		return 'addons/'.$addon;
	}

	public function getDependencies($addonName, $onlyMissing = false) {
		return $this->dependencyHelper($addonName, $onlyMissing);
	}

	public function dependencyHelper($addonName, $onlyMissing = false, $onlyFirst = false) {
		$addonService  = sly_Service_Factory::getAddOnService();
		$pluginService = sly_Service_Factory::getPluginService();
		$addons        = $addonService->getAvailableAddons();
		$result        = array();

		if (!$this->isAvailable($addonName)) {
			$this->loadConfig($addonName);
		}

		foreach ($addons as $addon) {
			// don't check yourself
			if ($addonName == $addon) continue;

			$requires = sly_makeArray($this->getProperty($addon, 'requires'));
			$inArray  = in_array($addonName, $requires);

			if ($inArray && $onlyFirst) {
				return array($addon);
			}

			if (!$onlyMissing || $inArray) {
				$result[] = $addon;
			}

			$plugins = $pluginService->getAvailablePlugins($addon);

			foreach ($plugins as $plugin) {
				$requires = sly_makeArray($pluginService->getProperty(array($addon, $plugin), 'requires'));
				$inArray  = in_array($addonName, $requires);

				if ($inArray && $onlyFirst) {
					return array(array($addon, $plugin));
				}

				if (!$onlyMissing || $inArray) {
					$result[] = array($addon, $plugin);
				}
			}
		}

		return $onlyFirst ? reset($result) : $result;
	}

	public function isRequired($addonName) {
		$dependency = $this->dependencyHelper($addonName, true, true);
		return empty($dependency) ? false : reset($dependency);
	}
}
