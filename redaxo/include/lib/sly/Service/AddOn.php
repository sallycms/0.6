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
class sly_Service_AddOn extends sly_Service_AddOn_Base
{
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
		$filesDir    = $addonDir.'files';
		$config      = sly_Core::config();
		
		$state = $this->extend('PRE', 'INSTALL', $addonName, true);

		// Prüfen des Addon Ornders auf Schreibrechte,
		// damit das Addon später wieder gelöscht werden kann
		
		$state = rex_is_writable($addonDir);

		if ($state) {
			if (is_readable($installFile)) {
				$this->req($installFile);

				$hasError = $config->has('ADDON/installmsg/'.$addonName);

				if ($hasError || !$this->isInstalled($addonName)) {
					$state = $this->I18N('no_install', $addonName).'<br />';
					
					if ($hasError) {
						$state .= $config->get('ADDON/installmsg/'.$addonName);
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
						$state = $this->I18N('config_not_found');
					}

					if ($installDump && $state === true && is_readable($installSQL)) {
						$state = rex_install_dump($installSQL);

						if ($state !== true) {
							$state = 'Error found in install.sql:<br />'.$state;
						}
					}
					
					if ($state === true) {
						// regenerate Addons file
						$state = $this->generateConfig();
					}
				}
			}
			else {
				$state = $this->I18N('install_not_found');
			}
		}
		
		$state = $this->extend('POST', 'INSTALL', $addonName, $state);
		
		// Dateien kopieren
		
		if ($state === true && is_dir($filesDir)) {
			if (!rex_copyDir($filesDir, $this->publicFolder($addonName), $REX['MEDIAFOLDER'])) {
				$state = $this->I18N('install_cant_copy_files');
			}
		}
		
		$state = $this->extend('POST', 'ASSET_COPY', $addonName, $state);

		if ($state !== true) {
			$this->setProperty($addonName, 'install', 0);
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
		$addonDir       = $this->baseFolder($addonName);
		$uninstallFile  = $addonDir.'uninstall.inc.php';
		$uninstallSQL   = $addonDir.'uninstall.sql';
		
		$state = $this->extend('PRE', 'UNINSTALL', $addonName, true);

		if (is_readable($uninstallFile)) {
			$this->req($uninstallFile);
			
			$hasError = $config->has('ADDON/installmsg/'.$addonName);

			if ($hasError || $this->isInstalled($addonName)) {
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
					// regenerate Addons file
					$state = $this->generateConfig();
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
			$this->setProperty($addonName, 'install', 1);
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
				$this->setProperty($addonName, 'status', 1);
				$state = $this->generateConfig();
			}
		}
		else {
			$state = $this->I18N('no_activation', $addonName);
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
			$this->setProperty($addonName, 'status', 0);
			$state = $this->generateConfig();
		}

		return $this->extend('POST', 'DEACTIVATE', $addonName, $state);
	}

	public function delete($addonName)
	{
		$state = $this->extend('PRE', 'DELETE', $addonName, true);
		
		if ($state === true) {
			$systemAddons = sly_Core::config()->get('SYSTEM_ADDONS');

			if (in_array($addonName, $systemAddons)) {
				$state = $this->I18N('addon_systemaddon_delete_not_allowed');
			}
			else {
				$state = $this->deleteHelper($addonName);
			}
		}
		
		return $this->extend('POST', 'DELETE', $addonName, $state);
	}
	
	protected function baseFolder($addonName)
	{
		return rex_addons_folder($addonName);
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
		$dir    = $config->get('DYNFOLDER').DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.$addonName;
		
		if (!is_dir($dir)) {
			mkdir($dir, $config->get('DIRPERM'), true);
		}
		
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

		$args     = func_get_args();
		$args[0] = $this->i18nPrefix.$args[0];

		return rex_call_func(array($I18N, 'msg'), $args, false);
	}

	protected function generateConfig()
	{
		return rex_generateAddons(array_keys($this->data['install']));
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
	
	public static function getIcon($addonName)
	{
		$directory = $this->publicFolder($addonName);
		$base      = $this->baseFolder($addonName);
		$icon      = $this->getProperty($addonName, 'icon', null);
		
		if ($icon === null) {
			if (file_exists($directory.'/images/icon.png')) {
				$icon = 'images/'.$addon.'/icon.png';
			}
			elseif (file_exists($directory.'/images/icon.gif')) {
				$icon = 'images/'.$addon.'/icon.gif';
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
	
	public function setProperty($addonName, $property, $value)
	{
		$rexAddon = rex_addon::create($addonName);

		if (!isset($rexAddon->data[$property])) {
			$rexAddon->data[$property] = array();
		}

		$rexAddon->data[$property][$rexAddon->name] = $value;
	}
	
	/**
	 * Gibt eine Eigenschaft des AddOns zurück.
	 *
	 * @param  string|array $addonName  Name des Addons
	 * @param  string       $property   Name der Eigenschaft
	 * @param  mixed        $default    Rückgabewert, falls die Eigenschaft nicht gefunden wurde
	 * @return string                   Wert der Eigenschaft des Addons
	 */
	public function getProperty($addonName, $property, $default = null)
	{
		$rexAddon = rex_addon::create($addonName);
		return isset($rexAddon->data[$property][$rexAddon->name]) ? $rexAddon->data[$property][$rexAddon->name] : $default;
	}
}
