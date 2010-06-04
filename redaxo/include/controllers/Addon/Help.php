<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_Controller_Addon_Help extends sly_Controller_Sally
{
	protected $func    = '';
	protected $addons  = null;
	protected $plugins = null;
	protected $addon   = null;
	protected $plugin  = null;
	protected $info    = '';
	protected $warning = '';
	
	public function init()
	{
		rex_title(t('addon'));
		print '<div class="sly-content">';
		
		$this->addons  = sly_Service_Factory::getService('AddOn');
		$this->plugins = sly_Service_Factory::getService('Plugin');
		
		$addon  = sly_request('addon', 'string', '');
		$plugin = sly_request('plugin', 'string', '');
		$addons = $this->addons->getRegisteredAddOns();
		
		$this->addon = in_array($addon, $addons) ? $addon : null;
		
		if ($this->addon) {
			$plugins      = $this->plugins->getRegisteredPlugins($this->addon);
			$this->plugin = in_array($plugin, $plugins) ? $plugin : null;
		}
		else {
			$_REQUEST['func'] = 'nohelp';
		}
	}
	
	public function teardown()
	{
		print '</div>';
	}
	
	public function nohelp()
	{
		$this->render('views/addon/list.phtml', array(
			'addons'  => $this->addons,
			'plugins' => $this->plugins,
			'info'    => '',
			'warning' => ''
		));
	}

	public function index()
	{
		$this->checkForNewComponents();
		
		$this->render('views/addon/help.phtml', array(
			'addons'  => $this->addons,
			'plugins' => $this->plugins,
			'addon'   => $this->addon,
			'plugin'  => $this->plugin
		));
	}
	
	protected function prepareAction()
	{
		return array(
			$this->plugin ? $this->plugins : $this->addons,
			$this->plugin ? array($this->addon, $this->plugin) : $this->addon
		);
	}
	
	protected function checkForNewComponents()
	{
		$addons  = rex_read_addons_folder();
		$plugins = array();
		
		foreach ($addons as $addon) {
			$plugins[$addon] = rex_read_plugins_folder($addon);
		}

		//verleiche dateisystem mit konfigurierten 
		// Wenn ein Addon in der Datei fehlt oder nicht mehr vorhanden ist, ändere den Dateiinhalt.
		
		$knownAddons = $this->addons->getRegisteredAddOns();
		
		if (count(array_diff($addons, $knownAddons)) > 0 || count(array_diff($knownAddons, $addons)) > 0) {
			if (($state = $this->addons->generateConfig()) !== true) {
				$this->warning .= $state;
			}
		}
		
		// dito für Plugins
		
		foreach ($addons as $addon) {
			$knownPlugins = $this->plugins->getRegisteredPlugins($addon);
			
			if (count(array_diff($plugins[$addon], $knownPlugins)) > 0 || count(array_diff($knownPlugins, $plugins[$addon])) > 0) {
				if (($state = $this->plugins->generateConfig()) !== true) {
					$this->warning .= $state;
					break;
				}
			}
		}
	}
	
	protected function t($key, $param = null)
	{
		global $I18N;
		$prefix = $this->plugin ? 'plugin_' : 'addon_';
		if ($this->plugin && is_array($param)) $param = $param[1];
		return $I18N->msg($prefix.$key, $param);
	}
	
	protected function call($method, $i18n)
	{
		list($service, $component) = $this->prepareAction();
		$this->warning = $service->$method($component);
		
		if ($this->warning === true) {
			$this->info    = $this->t($i18n, $component);
			$this->warning = '';
		}

		return $this->index();
	}

	public function install()    { return $this->call('install', 'installed');      }
	public function uninstall()  { return $this->call('uninstall', 'uninstalled');  }
	public function activate()   { return $this->call('activate', 'activated');     }
	public function deactivate() { return $this->call('deactivate', 'deactivated'); }
	public function delete()     { return $this->call('delete', 'deleted');         }

	public function checkPermission()
	{
		global $REX;
		return isset($REX['USER']) && $REX['USER']->isAdmin();
	}
}
