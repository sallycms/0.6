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
	protected $i18nPrefix;

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
		$config       = sly_Core::config();
		$staticFile   = $this->baseFolder($addonORplugin).'/static.yml';
		$defaultsFile = $this->baseFolder($addonORplugin).'/defaults.yml';

		if (file_exists($staticFile)) {
			$config->loadStatic($staticFile, $this->getConfPath($addonORplugin));
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
