<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup layout
 */
class sly_Layout_Navigation_Backend {
	private $groups;
	private $currentGroup;

	public function __construct() {
		$user = sly_Util_User::getCurrentUser();

		if (!is_null($user)) {
			$this->groups = array();
			$this->addGroup('system', 'translate:navigation_basis');
			$this->addGroup('addon', 'translate:navigation_addons');

			$isAdmin = $user->isAdmin();

			// Core-Seiten initialisieren

			if ($isAdmin || $user->hasStructureRight()) {
				$hasClangPerm = $isAdmin || count($user->getAllowedCLangs()) > 0;

				if ($hasClangPerm) {
					$this->addPage('system', 'structure');
				}

				$this->addPage('system', 'mediapool', null, true);
			}
			elseif ($user->hasRight('mediapool[]')) {
				$this->addPage('system', 'mediapool', null, true);
			}

			if ($isAdmin) {
				$this->addPage('system', 'user');
				$this->addPage('system', 'addon', 'translate:addons');
				$this->addPage('system', 'specials');
			}

			// AddOn-Seiten initialisieren
			$addonService  = sly_Service_Factory::getAddOnService();
			$pluginService = sly_Service_Factory::getPluginService();

			foreach ($addonService->getAvailableAddons() as $addon) {
				$link = '';
				$perm = $addonService->getProperty($addon, 'perm', '');
				$page = $addonService->getProperty($addon, 'page', '');

				if (!empty($page) && ($isAdmin || empty($perm) || $user->hasRight($perm))) {
					$name  = $addonService->getProperty($addon, 'name', '');
					$popup = $addonService->getProperty($addon, 'popup', false);

					$this->addPage('addon', strtolower($addon), $name, $popup, $page);
				}

				foreach ($pluginService->getAvailablePlugins($addon) as $plugin) {
					$pluginArray = array($addon, $plugin);
					$link        = '';
					$perm        = $pluginService->getProperty($pluginArray, 'perm', '');
					$page        = $pluginService->getProperty($pluginArray, 'page', '');

					if (!empty($page) && ($isAdmin || empty($perm) || $user->hasRight($perm))) {
						$name  = $pluginService->getProperty($pluginArray, 'name', '');
						$popup = $pluginService->getProperty($pluginArray, 'popup', false);

						$this->addPage('addon', strtolower($plugin), $name, $popup, $page);
					}
				}
			}
		}
	}

	public function createPage($name, $title = null, $popup = false, $pageParam = null) {
		return new sly_Layout_Navigation_Page($name, $title, $popup, $pageParam);
	}

	public function createGroup($name, $title) {
		return new sly_Layout_Navigation_Group($name, $title);
	}

	public function addGroup($name, $title) {
		$group = $this->createGroup($name, $title);
		$this->addGroupObj($group);
		return $group;
	}

	public function addPage($group, $name, $title = null, $popup = false, $pageParam = null) {
		$page  = $this->createPage($name, $title, $popup, $pageParam);
		$this->addPageObj($group, $page);
		return $page;
	}

	public function addGroupObj(sly_Layout_Navigation_Group $group) {
		$this->groups[$group->getName()] = $group;
		$this->currentGroup = $group;
	}

	public function addPageObj($group, sly_Layout_Navigation_Page $page) {
		$group = $group === null ? $this->currentGroup : $this->groups[$group];
		$group->addPage($page);
	}

	public function getCurrentGroup() {
		return $this->currentGroup;
	}

	public function getGroups() {
		return $this->groups;
	}

	public function getGroup($name) {
		return isset($this->groups[$name]) ? $this->groups[$name] : null;
	}

	public function get($name, $group) {
		$pages = $this->groups[$group]->getPages();
		foreach ($pages as $p) if ($p->getName() === $name || $p->getPageParam() === $name) return $p;
		return null;
	}

	public function find($name) {
		foreach (array_keys($this->groups) as $group) {
			$p = $this->get($name, $group);
			if ($p) return $p;
		}

		return null;
	}

	public function hasPage($name) {
		foreach (array_keys($this->groups) as $group) {
			if ($this->get($name, $group)) return true;
		}

		return false;
	}

	public function getActivePage() {
		foreach ($this->groups as $group) {
			foreach ($group->getPages() as $p) if ($p->isActive()) return $p;
		}

		return null;
	}

	public function getActiveGroup() {
		foreach ($this->groups as $group) {
			foreach ($group->getPages() as $p) if ($p->isActive()) return $group;
		}

		return null;
	}

	public function removeGroup($name) {
		$hasIt = isset($this->groups[$name]);
		unset($this->groups[$name]);
		return $hasIt;
	}

	public function removePage($name) {
		foreach ($this->groups as $gName => $group) {
			if ($this->get($name, $gName)) {
				return $group->removePage($name);
			}
		}

		return false;
	}

	public function removeSubpage($page, $subpage) {
		foreach (array_keys($this->groups) as $group) {
			$pageObj = $this->get($page, $group);

			if ($pageObj) {
				return $pageObj->removeSubpage($subpage);
			}
		}

		return false;
	}
}
