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
abstract class sly_Service_AddOn_Base {
	protected $addons;
	protected $data;

	public function __construct() {
		$this->data = sly_Core::config()->get('ADDON');
	}

	protected function req($filename) {
		global $REX, $I18N; // Nötig damit im Addon verfügbar
		require $filename;
	}

	private function getConfPath($addonORplugin) {
		if (is_array($addonORplugin)) {
			list($addon, $plugin) = $addonORplugin;
			return 'ADDON/'.$addon.'/plugins/'.$plugin;
		}
		else {
			return 'ADDON/'.$addonORplugin;
		}
	}

	public function loadConfig($addonORplugin) {
		global $REX;

		$config       = sly_Core::config();
		$staticFile   = $this->baseFolder($addonORplugin).'/static.yml';
		$defaultsFile = $this->baseFolder($addonORplugin).'/defaults.yml';

		if (file_exists($staticFile)) {
			$config->loadStatic($staticFile, $this->getConfPath($addonORplugin));

			foreach (array('perm', 'extperm') as $type) {
				$perm = sly_makeArray($this->getProperty($addonORplugin, $type, null));
				$upper = strtoupper($type);

				foreach ($perm as $p) {
					$REX[$upper][] = $p;
				}
			}
		}

		if (file_exists($defaultsFile)) {
			$config->loadProjectDefaults($defaultsFile, false, $this->getConfPath($addonORplugin));
		}
	}

	/**
	 * Check if a version number matches
	 *
	 * This will take a well-formed version number (X.Y.Z) and compare it against
	 * the system version. You can leave out parts from the right to make it
	 * more general (i.e. '0.2' will match any 0.2.x version).
	 *
	 * @param  string $version  the version number to check against
	 * @return boolean          true for a match, else false
	 */
	public function checkVersion($version) {
		$thisVersion = sly_Core::getVersion('X.Y.Z');
		return preg_match('#^'.preg_quote($version, '#').'.*#i', $thisVersion) == 1;
	}

	public function add($addonORplugin) {
		$this->setProperty($addonORplugin, 'install', false);
	}

	public function removeConfig($addonORplugin) {
		$config = sly_Core::config();
		$config->remove($this->getConfPath($addonORplugin));
	}

	public function getSupportPageEx($addonORplugin) {
		$supportPage = $this->getSupportPage($addonORplugin, '');

		if ($supportPage) {
			$supportPages = sly_makeArray($supportPage);
			$links        = array();

			foreach ($supportPages as $page) {
				$infos = parse_url($page);
				if (!isset($infos['host'])) $infos = parse_url('http://'.$page);
				if (!isset($infos['host'])) continue;

				$page = sprintf('%s://%s%s', $infos['scheme'], $infos['host'], isset($infos['path']) ? $infos['path'] : '');
				$host = substr($infos['host'], 0, 4) == 'www.' ? substr($infos['host'], 4) : $infos['host'];

				$links[] = '<a href="'.sly_html($page).'" class="sly-blank">'.sly_html($host).'</a>';
			}

			$supportPage = implode(', ', $links);
		}

		return $supportPage;
	}

	/**
	 * Aktiviert ein Addon
	 *
	 * @param $addonName Name des Addons
	 */
	public function activate($addonORplugin) {
		if ($this->isActivated($addonORplugin)) {
			return true;
		}

		if ($this->isInstalled($addonORplugin)) {
			$state = $this->extend('PRE', 'ACTIVATE', $addonORplugin, true);

			if ($state === true) {
				$this->checkUpdate($addonORplugin);
				$this->setProperty($addonORplugin, 'status', true);
			}
		}
		else {
			$state = t('no_activation', $addonORplugin);
		}

		return $this->extend('POST', 'ACTIVATE', $addonORplugin, $state);
	}

	/**
	 * Deaktiviert ein Plugin
	 *
	 * @param array $plugin  Plugin als array(addon, plugin)
	 */
	public function deactivate($addonORplugin) {
		if (!$this->isActivated($addonORplugin)) {
			return true;
		}

		$state = $this->extend('PRE', 'DEACTIVATE', $addonORplugin, true);

		if ($state === true) {
			$this->setProperty($addonORplugin, 'status', false);
		}

		return $this->extend('POST', 'DEACTIVATE', $addonORplugin, $state);
	}

	public function publicFolder($addonORplugin) {
		return $this->dynFolder('public', $addonORplugin);
	}

	public function internalFolder($addonORplugin) {
		return $this->dynFolder('internal', $addonORplugin);
	}

	public function deletePublicFiles($addonORplugin) {
		return $this->deleteFiles('public', $addonORplugin);
	}

	public function deleteInternalFiles($addonORplugin) {
		return $this->deleteFiles('internal', $addonORplugin);
	}

	protected function deleteFiles($type, $addonORplugin) {
		$dir   = $this->dynFolder($type, $addonORplugin);
		$state = $this->extend('PRE', 'DELETE_'.strtoupper($type), $addonORplugin, true);

		if ($state !== true) {
			return $state;
		}

		if (is_dir($dir) && !rex_deleteDir($dir, true)) {
			return $this->I18N('install_cant_delete_files');
		}

		return $this->extend('POST', 'DELETE_'.strtoupper($type), $addonORplugin, true);
	}

	protected function I18N() {
		$args    = func_get_args();
		$args[0] = $this->getI18NPrefix().$args[0];

		return call_user_func('t', $args, false);
	}

	public function isAvailable($addonORplugin) {
		return $this->isInstalled($addonORplugin) && $this->isActivated($addonORplugin);
	}

	public function isInstalled($addonORplugin) {
		return $this->getProperty($addonORplugin, 'install', false) == true;
	}

	public function isActivated($addonORplugin) {
		return $this->getProperty($addonORplugin, 'status', false) == true;
	}

	public function getAuthor($addonORplugin, $default = null) {
		return $this->getProperty($addonORplugin, 'author', $default);
	}

	public function getSupportPage($addonORplugin, $default = null) {
		return $this->getProperty($addonORplugin, 'supportpage', $default);
	}

	public function getVersion($addonORplugin, $default = null) {
		$version     = $this->getProperty($addonORplugin, 'version', null);
		$versionFile = $this->baseFolder($addonORplugin).'/version';

		if ($version === null && file_exists($versionFile)) {
			$version = file_get_contents($versionFile);
		}

		return $version === null ? $default : $version;
	}

	public function getKnownVersion($addonORplugin, $default = null) {
		$key     = $this->getVersionKey($addonORplugin);
		$version = sly_Util_Versions::get($key);

		return $version === false ? $default : $version;
	}

	public function copyAssets($addonORplugin) {
		$addonDir  = $this->baseFolder($addonORplugin);
		$assetsDir = sly_Util_Directory::join($addonDir, 'assets');
		$state     = true;

		if (!is_dir($assetsDir)) return true;

		if (!rex_copyDir($assetsDir, $this->publicFolder($addonORplugin), SLY_MEDIAFOLDER)) {
			$state = t('install_cant_copy_files');
		}
		else {
			$folder    = $this->publicFolder($addonORplugin);
			$targetDir = new sly_Util_Directory($folder);
			$files     = $targetDir->listRecursive(false, true);
			$exclude   = sly_makeArray($this->getProperty($addonORplugin, 'noscaffold', array()));

			foreach ($files as $filename) {
				if (sly_Util_String::endsWith($filename, '.css')) {
					$relName  = substr($filename, strlen($folder) + 1);
					$relName  = str_replace('\\', '/', $relName);
					$excluded = false;

					foreach ($exclude as $pattern) {
						if (fnmatch($pattern, $relName)) {
							$excluded = true;
							break;
						}
					}

					if (!$excluded) {
						$css = sly_Util_Scaffold::process($filename);
						file_put_contents($filename, $css);
					}
				}
			}
		}

		return $state;
	}

	public function checkUpdate($addonORplugin) {
		$version = $this->getVersion($addonORplugin, false);
		$key     = $this->getVersionKey($addonORplugin);
		$known   = sly_Util_Versions::get($key, false);

		if ($known !== false && $version !== false && $known !== $version) {
			$updateFile = $this->baseFolder($addonORplugin).'update.inc.php';

			if (file_exists($updateFile)) {
				$this->req($updateFile);
			}
		}

		if ($version !== false && $known !== $version) {
			sly_Util_Versions::set($key, $version);
		}
	}

//	abstract public function install($addonName);         // Installieren
//	abstract public function uninstall($addonName);       // Deinstallieren
//	abstract public function activate($addonName);        // Aktivieren
//	abstract public function deactivate($addonName);      // Deaktivieren
//	abstract public function delete($addonName);          // Löschen
//	abstract public function generateConfig();            // Config-Datei neu generieren (z. B. addons.inc.php)
//	abstract public function publicFolder($addonName);    // data/dyn/public/foo
//	abstract public function internalFolder($addonName);  // data/dyn/internal/foo
//
//	abstract protected function baseFolder($addonName);   // sally/include/addons/foo
}
