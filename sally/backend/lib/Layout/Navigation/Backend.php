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
		$this->groups = array();
		$this->addGroup('system', 'translate:navigation_basis');
		$this->addGroup('addon', 'translate:navigation_addons');
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
}
