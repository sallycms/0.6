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
class sly_Service_AddOn extends sly_Service_AddOn_Base
{
	protected static $addonsLoaded = array();

	public function __construct()
	{
		$this->data       = sly_Core::config()->get('ADDON');
		$this->i18nPrefix = 'addon_';
	}

	public function install($addonName, $installDump = true)
	{
		global $REX;

		$addonDir    = $this->baseFolder($addonName);
		$installFile = $addonDir.'install.inc.php';
		$installSQL  = $addonDir.'install.sql';
		$configFile  = $addonDir.'config.inc.php';
		$filesDir    = $addonDir.'assets';

		$state = $this->extend('PRE', 'INSTALL', $addonName, true);

		// check requirements

		if ($state) {
			if (!$this->isInstalled($addonName)) {
				$this->loadConfig($addonName);
			}

			$requires = $this->getProperty($addonName, 'requires');

			if (!empty($requires)) {
				$requires = sly_makeArray($requires);

				foreach ($requires as $requiredAddon) {
					if (!$this->isAvailable($requiredAddon)) {
						//TODO I18n
						return 'The addOn '.$requiredAddon.' is required to install this addOn.';
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
					return 'This addOn is not marked as compatible with your SallyCMS version ('.sly_Core::getVersion('X.Y.Z').').';
				}
			}
		}

		// Prüfen des Addon Ornders auf Schreibrechte,
		// damit das Addon später wieder gelöscht werden kann

		if ($state) {
			if (is_readable($installFile)) {
				try {
					$this->req($installFile);
				}catch (Exception $e) {
					$installError = 'Es ist eine unerwartete Ausnahme während der Installation aufgetreten: '.$e->getMessage();
				}

				$hasError = !empty($installError);

				if ($hasError) {
					$state = t('no_install', $addonName).'<br />';

					if ($hasError) {
						$state .= $installError;
					}
					else {
						$state .= $this->I18N('no_reason');
					}
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
				$state = t('install_not_found');
			}
		}

		$state = $this->extend('POST', 'INSTALL', $addonName, $state);

		// Dateien kopieren

		if ($state === true && is_dir($filesDir)) {
			$state = $this->copyAssets($addonName);
		}

		$state = $this->extend('POST', 'ASSET_COPY', $addonName, $state);

		if ($state !== true) {
			$this->setProperty($addonName, 'install', false);
		}

		return $state;
	}

	/**
	 * De-installiert ein Addon
	 *
	 * @param $addonName Name des Addons
	 */
	public function uninstall($addonName)
	{
		$addonDir      = $this->baseFolder($addonName);
		$uninstallFile = $addonDir.'uninstall.inc.php';
		$uninstallSQL  = $addonDir.'uninstall.sql';
		$config        = sly_Core::config();

		$state = $this->extend('PRE', 'UNINSTALL', $addonName, true);

		if (is_readable($uninstallFile)) {
			$this->req($uninstallFile);

			$hasError = $config->has('ADDON/installmsg/'.$addonName);

			if ($hasError) {
				$state = $this->I18N('no_uninstall', $addonName).'<br />';

				if ($hasError) {
					$state .= $config->get('ADDON/installmsg/'.$addonName);
				}
				else {
					$state .= $this->I18N('no_reason');
				}
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
			$state = $this->I18N('uninstall_not_found');
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

	/**
	 * Aktiviert ein Addon
	 *
	 * @param $addonName Name des Addons
	 */
	public function activate($addonName)
	{
		if ($this->isActivated($addonName)) {
			return true;
		}

		if ($this->isInstalled($addonName)) {
			$state = $this->extend('PRE', 'ACTIVATE', $addonName, true);

			if ($state === true) {
				$this->setProperty($addonName, 'status', true);
			}
		}
		else {
			$state = t('no_activation', $addonName);
		}

		return $this->extend('POST', 'ACTIVATE', $addonName, $state);
	}

	/**
	 * Deaktiviert ein Addon
	 *
	 * @param $addonName Name des Addons
	 */
	public function deactivate($addonName)
	{
		if (!$this->isActivated($addonName)) {
			return true;
		}

		$state = $this->extend('PRE', 'DEACTIVATE', $addonName, true);

		if ($state === true) {
			$this->setProperty($addonName, 'status', false);
		}

		return $this->extend('POST', 'DEACTIVATE', $addonName, $state);
	}

	public function baseFolder($addonName)
	{
		$dir = SLY_INCLUDE_PATH.DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR;
		if (!empty($addonName)) $dir .= $addonName.DIRECTORY_SEPARATOR;
		return $dir;
	}

	public function publicFolder($addonName)
	{
		return $this->dynFolder('public', $addonName);
	}

	public function internalFolder($addonName)
	{
		return $this->dynFolder('internal', $addonName);
	}

	protected function dynFolder($type, $addonName)
	{
		$config = sly_Core::config();
		$dir    = SLY_DYNFOLDER.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.$addonName;

		sly_Util_Directory::create($dir);
		return $dir;
	}

	protected function dynPath($type, $addonName)
	{
		$config = sly_Core::config();
		$dir    = SLY_BASE.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.$addonName;

		sly_Util_Directory::create($dir);
		return $dir;
	}

	protected function extend($time, $type, $addonName, $state)
	{
		return rex_register_extension_point('SLY_ADDON_'.$time.'_'.$type, $state, array('addon' => $addonName));
	}

	public function deletePublicFiles($addonName)
	{
		return $this->deleteFiles('public', $addonName);
	}

	public function deleteInternalFiles($addonName)
	{
		return $this->deleteFiles('internal', $addonName);
	}

	protected function deleteFiles($type, $addonName)
	{
		$dir   = $this->dynFolder($type, $addonName);
		$state = $this->extend('PRE', 'DELETE_'.strtoupper($type), $addonName, true);

		if ($state !== true) {
			return $state;
		}

		if (is_dir($dir) && !rex_deleteDir($dir, true)) {
			return $this->I18N('install_cant_delete_files');
		}

		return $this->extend('POST', 'DELETE_'.strtoupper($type), $addonName, true);
	}

	protected function I18N()
	{
		global $I18N;

		$args    = func_get_args();
		$args[0] = $this->i18nPrefix.$args[0];

		return rex_call_func(array($I18N, 'msg'), $args, false);
	}

	public function isAvailable($addonName)
	{
		return $this->isInstalled($addonName) && $this->isActivated($addonName);
	}

	public function isInstalled($addonName)
	{
		return $this->getProperty($addonName, 'install', false) == true;
	}

	public function isActivated($addonName)
	{
		return $this->getProperty($addonName, 'status', false) == true;
	}

	public function getVersion($addonName, $default = null)
	{
		$version     = $this->getProperty($addonName, 'version', null);
		$versionFile = $this->baseFolder($addonName).'/version';

		if ($version === null && file_exists($versionFile)) {
			$version = file_get_contents($versionFile);
		}

		return $version === null ? $default : $version;
	}

	public function getAuthor($addonName, $default = null)
	{
		return $this->getProperty($addonName, 'author', $default);
	}

	public function getSupportPage($addonName, $default = null)
	{
		return $this->getProperty($addonName, 'supportpage', $default);
	}

	public function getIcon($addonName)
	{
		$directory = $this->publicFolder($addonName);
		$base      = $this->baseFolder($addonName);
		$icon      = $this->getProperty($addonName, 'icon', null);

		if ($icon === null) {
			if (file_exists($directory.'/images/icon.png')) {
				$icon = 'images/'.$addonName.'/icon.png';
			}
			elseif (file_exists($directory.'/images/icon.gif')) {
				$icon = 'images/'.$addonName.'/icon.gif';
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
	 * @param  string $addon     Name des Addons
	 * @param  string $property  Name der Eigenschaft
	 * @param  mixed  $property  Wert der Eigenschaft
	 * @return mixed             der gesetzte Wert
	 */
	public function setProperty($addonName, $property, $value)
	{
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
	public function getProperty($addonName, $property, $default = null)
	{
		return sly_Core::config()->has('ADDON/'.$addonName.'/'.$property) ? sly_Core::config()->get('ADDON/'.$addonName.'/'.$property) : $default;
	}

	/**
	 * Gibt ein Array aller registrierten Addons zurück.
	 *
	 * Ein Addon ist registriert, wenn es dem System bekannt ist (addons.yaml).
	 *
	 * @return array  Array aller registrierten Addons
	 */
	public function getRegisteredAddons()
	{
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
	public function getAvailableAddons()
	{
		$avail = array();

		foreach ($this->getRegisteredAddons() as $addonName) {
			if ($this->isAvailable($addonName)) $avail[] = $addonName;
		}

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
	public function isSystemAddon($addonName)
	{
		return false;
	}

	public function loadAddon($addonName) {
		if(in_array($addonName, self::$addonsLoaded)) return true;

		$this->loadConfig($addonName);

		$requires = $this->getProperty($addonName, 'requires');
		if(!empty($requires)) {
			if(!is_array($requires)) $requires = sly_makeArray($requires);
			foreach($requires as $requiredAddon) {
				$this->loadAddon($requiredAddon);
			}
		}

		$addonConfig = $this->baseFolder($addonName).'config.inc.php';

		if (file_exists($addonConfig)) {
			global $REX, $I18N;
			require_once $addonConfig;
		}

		self::$addonsLoaded[] = $addonName;
	}
}
