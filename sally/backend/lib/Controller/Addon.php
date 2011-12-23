<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
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
		if (!sly_get('json', 'boolean')) {
			$layout = sly_Core::getLayout();
			$layout->pageHeader(t('addons'));
		}

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

		$data = $this->buildDataList();

		print $this->render('addon/list.phtml', array(
			'addons'  => $this->addons,
			'plugins' => $this->plugins,
			'tree'    => $data,
			'stati'   => $this->buildStatusList($data),
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
	}

	public function install() {
		$this->call('install', 'installed');

		if ($this->warning === '') {
			$this->call('activate', 'activated');
		}

		return $this->sendResponse();
	}

	public function uninstall()  { $this->call('uninstall', 'uninstalled');    return $this->sendResponse(); }
	public function activate()   { $this->call('activate', 'activated');       return $this->sendResponse(); }
	public function deactivate() { $this->call('deactivate', 'deactivated');   return $this->sendResponse(); }
	public function reinit()     { $this->call('copyAssets', 'assets_copied'); return $this->sendResponse(); }

	public function fullinstall() {
		list($service, $component) = $this->prepareAction();

		$todo = $this->getInstallList($component);

		if (!empty($todo)) {
			$now = reset($todo);

			// pretend that we're about to work on $now
			if (is_array($now)) {
				$this->addon  = $now[0];
				$this->plugin = $now[1];
			}
			else {
				$this->addon  = $now;
				$this->plugin = '';
			}

			list($service, $component) = $this->prepareAction();

			// if not installed, install it
			if (!$service->isInstalled($component)) {
				$this->call('install', 'installed');
			}

			// if not activated and install went OK, activate it
			if (!$service->isAvailable($component) && $this->warning === '') {
				$this->call('activate', 'activated');
			}

			// if everything worked out fine, we can redirect to the next component
			if ($this->warning === '' && count($todo) > 1) {
				sly_Util_HTTP::redirect($_SERVER['REQUEST_URI'], array(), '', 302);
			}
		}

		return $this->sendResponse();
	}

	public function checkPermission() {
		$user = sly_Util_User::getCurrentUser();
		return $user && ($user->isAdmin() || $user->hasRight('pages', 'addons'));
	}

	private function sendResponse() {
		if (sly_get('json', 'boolean')) {
			header('Content-Type: application/json; charset=UTF-8');
			while (ob_get_level()) ob_end_clean();
			ob_start('ob_gzhandler');

			$data = $this->buildDataList();

			$response = array(
				'status'  => !empty($this->info),
				'message' => empty($this->info) ? $this->warning : $this->info,
				'stati'   => $this->buildStatusList($data)
			);

			print json_encode($response);
			die;
		}

		return $this->index();
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

	private function getComponentDetails($component, $type) {
		static $reqCache = array();
		static $depCache = array();

		$service = $type === 'addon' ? $this->addons : $this->plugins;
		$key     = is_array($component) ? $component[0].'/'.$component[1] : $component;

		if (!isset($reqCache[$key])) {
			$reqCache[$key] = $service->getRequirements($component);
			$depCache[$key] = $service->getDependencies($component);
		}

		$requirements = $reqCache[$key];
		$dependencies = $depCache[$key];
		$missing      = array();
		$required     = $service->isRequired($component) !== false;
		$installed    = $service->isInstalled($component);
		$activated    = $installed ? $service->isActivated($component) : false;
		$compatible   = $service->isCompatible($component);
		$version      = $service->getVersion($component);
		$author       = $service->getSupportPageEx($component);
		$usable       = $compatible ? $this->canBeUsed($component) : false;

		foreach ($requirements as $req) {
			if (is_array($req)) {
				if (!$this->plugins->isAvailable($req)) $missing[] = $req;
			}
			else {
				if (!$this->addons->isAvailable($req)) $missing[] = $req;
			}
		}

		return compact('requirements', 'dependencies', 'missing', 'required', 'installed', 'activated', 'compatible', 'usable', 'version', 'author');
	}

	/**
	 * Check whether a component can be used
	 *
	 * To make this method return true, all required components must be present,
	 * compatible and themselves be usable.
	 *
	 * @param  mixed $component
	 * @return boolean
	 */
	private function canBeUsed($component) {
		$service = is_string($component) ? $this->addons : $this->plugins;

		if (!$service->exists($component))       return false;
		if (!$service->isCompatible($component)) return false;

		$requirements = $service->getRequirements($component);

		foreach ($requirements as $requirement) {
			if (!$this->canBeUsed($requirement)) return false;
		}

		return true;
	}

	/**
	 * Determine what components to install
	 *
	 * This method will walk through all requirements and collect a list of
	 * components that need to be installed to install the $component. The list
	 * is ordered ($component is always the last element). Already activated
	 * components will not be included (so the result can be empty if $component
	 * is also already activated).
	 *
	 * @param  mixed $component  plugin or addOn
	 * @param  array $list       current stack (used internally)
	 * @return array             install list
	 */
	private function getInstallList($component, array $list = array()) {
		$service      = is_string($component) ? $this->addons : $this->plugins;
		$idx          = array_search($component, $list);
		$requirements = $service->getRequirements($component);

		if ($idx !== false) {
			unset($list[$idx]);
			$list = array_values($list);
		}

		if (!$service->isAvailable($component)) {
			array_unshift($list, $component);
		}

		foreach ($requirements as $requirement) {
			$list = $this->getInstallList($requirement, $list);
		}

		return $list;
	}

	private function buildDataList() {
		$addons = array();

		foreach ($this->addons->getRegisteredAddOns() as $addon) {
			$pluginList = $this->plugins->getRegisteredPlugins($addon);
			$plugins    = array();

			foreach ($pluginList as $plugin) {
				$comp             = array($addon, $plugin);
				$plugins[$plugin] = $this->getComponentDetails($comp, 'plugin');
			}

			$info            = $this->getComponentDetails($addon, 'addon');
			$info['plugins'] = $plugins;

			$addons[$addon] = $info;
		}

		return $addons;
	}

	private function buildStatusList(array $dataList) {
		$result = array();

		foreach ($dataList as $addon => $aInfo) {
			$classes = array('sly-addon');

			// build class list for all relevant stati

			if (!empty($aInfo['plugins'])) {
				$classes[] = 'p1';

				foreach ($aInfo['plugins'] as $pInfo) {
					if ($pInfo['activated']) {
						$classes[] = 'pa1';
						$classes[] = 'd1';  // assume implicit dependency of plugins from their parent addOns
						break;
					}
				}
			}
			else {
				$classes[] = 'p0';
			}

			if (!in_array('pa1', $classes)) {
				$classes[] = 'd'.intval($aInfo['required']);
			}
			else {
				$classes[] = 'pa0';

				foreach (array_keys($aInfo['plugins']) as $plugin) {
					$aInfo['requirements'][] = $addon.'/'.$plugin;
				}
			}

			$classes[] = 'i'.intval($aInfo['installed']);
			$classes[] = 'a'.intval($aInfo['activated']);
			$classes[] = 'c'.intval($aInfo['compatible']);
			$classes[] = 'r'.intval($aInfo['requirements']);
			$classes[] = 'ro'.(empty($aInfo['missing']) ? 1 : 0);
			$classes[] = 'u'.intval($aInfo['usable']);

			$result[$addon] = array(
				'classes' => implode(' ', $classes),
				'deps'    => $this->buildDepsInfo($aInfo)
			);

			foreach ($aInfo['plugins'] as $plugin => $pInfo) {
				$key     = $addon.'/'.$plugin;
				$classes = array('sly-plugin');

				$pInfo['requirements'][] = $addon;
				$pInfo['requirements'] = array_unique($pInfo['requirements']);

				$classes[] = 'i'.intval($pInfo['installed']);
				$classes[] = 'a'.intval($pInfo['activated']);
				$classes[] = 'd'.intval($pInfo['required']);
				$classes[] = 'c'.intval($pInfo['compatible']);
				$classes[] = 'r'.intval($pInfo['requirements']);
				$classes[] = 'ro'.(empty($pInfo['missing']) ? 1 : 0);
				$classes[] = 'u'.intval($pInfo['usable']);

				$result[$key] = array(
					'classes' => implode(' ', $classes),
					'deps'    => $this->buildDepsInfo($pInfo)
				);
			}
		}

		return $result;
	}

	private function buildDepsInfo(array $info) {
		if ($info['required']) {
			$names = array();

			foreach ($info['dependencies'] as $comp) {
				$names[] = is_array($comp) ? reset($comp).' / '.end($comp) : $comp;
			}

			$isRequiredTitle = sly_html(t('is_required', sly_Util_String::humanImplode($names)));
		}
		else {
			$isRequiredTitle = '';
		}

		if ($info['requirements']) {
			$names = array();

			foreach ($info['requirements'] as $comp) {
				$names[] = is_array($comp) ? reset($comp).' / '.end($comp) : $comp;
			}

			$requiresTitle = t('requires').' '.sly_Util_String::humanImplode($names);
		}
		else {
			$requiresTitle = '';
		}

		$texts = array_filter(array($requiresTitle, $isRequiredTitle));
		if (empty($texts)) $texts[] = t('no_dependencies');
		return implode(' &amp; ', $texts);
	}
}
