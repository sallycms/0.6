<?php

class sly_Controller_Addon extends sly_Controller_Sally
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
			unset($_REQUEST['func']);
		}
	}
	
	public function teardown()
	{
		print '</div>';
	}

	public function index()
	{
		$this->checkForNewComponents();
		
		$this->render('views/addon/list.phtml', array(
			'addons'  => $this->addons,
			'plugins' => $this->plugins,
			'info'    => $this->info,
			'warning' => $this->warning
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
		$config = sly_Core::config();
       	$addons  = rex_read_addons_folder();
		$plugins = array();
		
		foreach ($addons as $addon) {
			$plugins[$addon] = rex_read_plugins_folder($addon);
		}

		// Vergleiche Addons aus dem Verzeichnis addons/ mit den Einträgen in addons.yaml.
		// Wenn ein Addon in der Datei fehlt oder nicht mehr vorhanden ist, ändere den Dateiinhalt.
		
		$knownAddons = $this->addons->getRegisteredAddOns();

		foreach(array_diff($addons, $knownAddons) as $addon){
			$config->set('ADDON/install/'.$addon, false);
			$config->set('ADDON/status/'.$addon, false);
		}
		foreach(array_diff($knownAddons, $addons) as $addon){
			$config->remove('ADDON/install/'.$addon);
			$config->remove('ADDON/status/'.$addon);
		}

		// dito für Plugins
		
		foreach ($addons as $addon) {
			$knownPlugins = $this->plugins->getRegisteredPlugins($addon);
			
			foreach(array_diff($plugins[$addon], $knownPlugins) as $plugin){
				$config->set('ADDON/plugins/'.$addon.'/install/'.$plugin, false);
				$config->set('ADDON/plugins/'.$addon.'/status/'.$plugin, false);
			}
			foreach(array_diff($knownPlugins, $plugins[$addon]) as $plugin){
				$config->remove('ADDON/plugins/'.$addon.'/install/'.$plugin);
				$config->remove('ADDON/plugins/'.$addon.'/status/'.$plugin);
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
