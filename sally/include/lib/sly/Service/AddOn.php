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
class sly_Service_AddOn extends sly_Service_AddOn_Base {
	protected static $addonsLoaded = array();

	public function install($addonName, $installDump = true) {
		$addonDir    = $this->baseFolder($addonName);
		$installFile = $addonDir.'install.inc.php';
		$installSQL  = $addonDir.'install.sql';
		$configFile  = $addonDir.'config.inc.php';
		$filesDir    = $addonDir.'assets';

		$state = $this->extend('PRE', 'INSTALL', $addonName, true);

		// check requirements

		if ($state) {
			if (!$this->isAvailable($addonName)) {
				$this->loadConfig($addonName);
			}

			$requires = $this->getProperty($addonName, 'requires');

			if (!empty($requires)) {
				$requires = sly_makeArray($requires);

				foreach ($requires as $requiredAddon) {
					if (!$this->isAvailable($requiredAddon)) {
						return t('addon_addon_required', $requiredAddon, $addonName);
					}
				}
			}

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
		}

		// Prüfen des Addon Ornders auf Schreibrechte,
		// damit das Addon später wieder gelöscht werden kann

		if ($state) {
			if (is_readable($installFile)) {
				try {
					$this->req($installFile);
				}
				catch (Exception $e) {
					$installError = t('addon_no_install', $addonName, $e->getMessage());
				}

				if (!empty($installError)) {
					$state = t('addon_no_install', $addonName).'<br />'.$installError;
				}
				else {
					if (is_readable($configFile)) {
						if (!$this->isActivated($addonName)) {
							$this->req($configFile);
						}
					}
					else {
						$state = t('config_not_found');
					}

					if ($installDump && $state === true && is_readable($installSQL)) {
						$state = rex_install_dump($installSQL);

						if ($state !== true) {
							$state = 'Error found in install.sql:<br />'.$state;
						}
					}

					$this->setProperty($addonName, 'install', true);
				}
			}
			else {
				$state = t('addon_install_not_found');
			}
		}

		$state = $this->extend('POST', 'INSTALL', $addonName, $state);

		// copy assets to data/dyn/public

		if ($state === true && is_dir($filesDir)) {
			$state = $this->copyAssets($addonName);
		}

		$state = $this->extend('POST', 'ASSET_COPY', $addonName, $state);

		if ($state !== true) {
			$this->setProperty($addonName, 'install', false);
		}
		else {
			// store current addOn version
			$version = $this->getProperty($addonName, 'version', false);

			if ($version !== false) {
				sly_Util_Versions::set('addons/'.$addonName, $version);
			}
		}

		return $state;
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

		$state = $this->extend('PRE', 'UNINSTALL', $addonName, true);

		if (is_readable($uninstallFile)) {
			try {
				$this->req($uninstallFile);
			}
			catch (Exception $e) {
				$installError = t('addon_no_uninstall', $addonName, $e->getMessage());
			}

			if (!empty($installError)) {
				$state = t('addon_no_uninstall', $addonName).'<br />'.$installError;
			}
			else {
				$state = $this->deactivate($addonName);

				if ($state === true && is_readable($uninstallSQL)) {
					$state = rex_install_dump($uninstallSQL);

					if ($state !== true) {
						$state = 'Error found in uninstall.sql:<br />'.$state;
					}
				}

				if ($state === true) {
					$this->setProperty($addonName, 'install', false);
				}
			}
		}
		else {
			$state = t('addon_uninstall_not_found');
		}

		$state = $this->extend('POST', 'UNINSTALL', $addonName, $state);

		if ($state === true) $state = $this->deletePublicFiles($addonName);
		if ($state === true) $state = $this->deleteInternalFiles($addonName);

		$state = $this->extend('POST', 'ASSET_DELETE', $addonName, $state);

		if ($state !== true) {
			$this->setProperty($addonName, 'install', true);
		}

		return $state;
	}

	public function baseFolder($addonName) {
		$dir = SLY_INCLUDE_PATH.DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR;
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
}
