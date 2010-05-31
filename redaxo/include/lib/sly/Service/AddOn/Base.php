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
abstract class sly_Service_AddOn_Base
{
	protected $addons;
	protected $data;
	protected $i18nPrefix;
	
	protected function deleteHelper($addonNameOrPlugin)
	{
		$state  = true;
		$state &= $this->uninstall($addonNameOrPlugin);
		$state &= rex_deleteDir($this->baseFolder($addonNameOrPlugin), true);
		$state &= $this->generateConfig();

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
		
		// Synchronisation mit sly_Configuration
		//sly_Core::config()->set('ADDON', $REX['ADDON']);
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
