<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Addon extends sly_Controller_Backend {
	protected $func    = '';
	protected $addons  = null;
	protected $plugins = null;
	protected $addon   = null;
	protected $plugin  = null;
	protected $info    = '';
	protected $warning = '';

	public function init() {
		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('addons'));

		$this->addons  = sly_Service_Factory::getAddOnService();
		$this->plugins = sly_Service_Factory::getPluginService();

		$addon  = sly_request('addon', 'string', '');
		$plugin = sly_request('plugin', 'string', '');
		$addons = $this->addons->getRegisteredAddOns();

		$this->addon  = in_array($addon, $addons) ? $addon : null;
		$this->plugin = null;

		if ($this->addon) {
			$plugins      = $this->plugins->getRegisteredPlugins($this->addon);
			$this->plugin = in_array($plugin, $plugins) ? $plugin : null;
		}
		else {
			unset($_REQUEST['func']);
		}
	}

	public function index() {
		$this->checkForNewComponents();

		print $this->render('addon/list.phtml', array(
			'addons'  => $this->addons,
			'plugins' => $this->plugins,
			'info'    => $this->info,
			'warning' => $this->warning
		));
	}

	protected function prepareAction() {
		return array(
			$this->plugin ? $this->plugins : $this->addons,
			$this->plugin ? array($this->addon, $this->plugin) : $this->addon
		);
	}

	protected function checkForNewComponents() {
		$config  = sly_Core::config();
		$addons  = $this->readAddOns();
		$plugins = array();

		foreach ($addons as $addon) {
			$plugins[$addon] = $this->readPlugins($addon);
		}

		// Vergleiche Addons aus dem Verzeichnis addons/ mit den Einträgen in addons.yaml.
		// Wenn ein Addon in der Datei fehlt oder nicht mehr vorhanden ist, ändere den Dateiinhalt.

		$knownAddons = $this->addons->getRegisteredAddOns();

		foreach (array_diff($addons, $knownAddons) as $addon){
			$this->addons->add($addon);
		}

		foreach (array_diff($knownAddons, $addons) as $addon){
			$this->addons->removeConfig($addon);
		}

		// dito für Plugins

		foreach ($addons as $addon) {
			$knownPlugins = $this->plugins->getRegisteredPlugins($addon);

			foreach (array_diff($plugins[$addon], $knownPlugins) as $plugin){
				$this->plugins->add(array($addon, $plugin));
			}

			foreach (array_diff($knownPlugins, $plugins[$addon]) as $plugin){
				$this->plugins->removeConfig(array($addon, $plugin));
			}
		}
	}

	protected function t($key, $param = null) {
		$prefix = $this->plugin ? 'plugin_' : 'addon_';
		if ($this->plugin && is_array($param)) $param = $param[1];
		return t($prefix.$key, $param);
	}

	protected function call($method, $i18n) {
		list($service, $component) = $this->prepareAction();
		$this->warning = $service->$method($component);

		if ($this->warning === true || $this->warning === 1) {
			$this->info    = $this->t($i18n, $component);
			$this->warning = '';
		}

		return $this->index();
	}

	public function install()    { return $this->call('install', 'installed');        }
	public function uninstall()  { return $this->call('uninstall', 'uninstalled');    }
	public function activate()   { return $this->call('activate', 'activated');       }
	public function deactivate() { return $this->call('deactivate', 'deactivated');   }
	public function assets()     { return $this->call('copyAssets', 'assets_copied'); }

	public function checkPermission() {
		$user = sly_Util_User::getCurrentUser();
		return !is_null($user) && $user->isAdmin();
	}

	private function readAddOns() {
		$dir = sly_Service_Factory::getAddOnService()->baseFolder(null);
		return $this->readDir($dir);
	}

	private function readPlugins($addon) {
		$dir = sly_Service_Factory::getPluginService()->baseFolder($addon);
		return $this->readDir($dir);
	}

	private function readDir($dir) {
		$dir = new sly_Util_Directory($dir);
		return $dir->exists() ? $dir->listPlain(false, true) : array();
	}
}
