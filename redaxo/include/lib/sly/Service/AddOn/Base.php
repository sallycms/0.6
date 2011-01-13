<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
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
abstract class sly_Service_AddOn_Base
{
	protected $addons;
	protected $data;
	protected $i18nPrefix;

	protected function deleteHelper($addonORplugin)
	{
		$state  = true;
		$state &= $this->uninstall($addonORplugin);
		$state &= rex_deleteDir($this->baseFolder($addonORplugin), true);

		if ($state){
			$this->removeConfig($addonORplugin);
		}

		return $state;
	}

	protected function req($filename, $addonName)
	{
		global $REX, $I18N; // Nötig damit im Addon verfügbar

		try {
			require $filename;
		}
		catch (Exception $e) {
			$REX['ADDON']['installmsg'][$addonName] =
				'Es ist eine unerwartete Ausnahme während der Installation aufgetreten: '.$e->getMessage();
		}
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

	public function loadConfig($addonORplugin)
	{
		$config       = sly_Core::config();
		$staticFile   = $this->baseFolder($addonORplugin).'/static.yml';
		$defaultsFile = $this->baseFolder($addonORplugin).'/defaults.yml';

		if (file_exists($staticFile)){
			$config->loadStatic($staticFile, $this->getConfPath($addonORplugin));
		}

		if (file_exists($defaultsFile)){
			$config->loadProjectDefaults($defaultsFile, false, $this->getConfPath($addonORplugin));
		}
	}

	public function add($addonORplugin){
		$this->setProperty($addonORplugin, 'install', false);
	}

	public function removeConfig($addonORplugin){
		$config = sly_Core::config();
		$config->remove($this->getConfPath($addonORplugin));
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
//	abstract protected function baseFolder($addonName);   // redaxo/include/addons/foo
}
