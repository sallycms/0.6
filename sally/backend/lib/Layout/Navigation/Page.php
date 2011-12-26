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
class sly_Layout_Navigation_Page {
	private $name;
	private $title;
	private $popup;
	private $pageParam;
	private $hidden;
	private $subpages;
	private $forceStatus;

	public function __construct($name, $title = null, $popup = false, $pageParam = null) {
		$this->setName($name);
		$this->setTitle($title);
		$this->setPopup($popup);
		$this->setPageParam($pageParam);
		$this->setHidden(false);
		$this->forceStatus(null);
		$this->subpages = array();
	}

	public function isActive() {
		$forced = $this->forceStatus;
		$isPage = sly_Core::getCurrentPage() == $this->pageParam;
		foreach($this->subpages as $subpage) {
			$isPage |= $subpage->isActive();
		}
		return $forced !== null ? $forced : $isPage;
	}

	public function getName()      { return $this->name;      }
	public function getTitle()     { return $this->title;     }
	public function isPopup()      { return $this->popup;     }
	public function getPageParam() { return $this->pageParam; }
	public function isHidden()     { return $this->hidden;    }
	public function getSubpages()  { return $this->subpages;  }

	public function setName($name) {
		$this->name = trim($name);
		return $this->name;
	}

	public function forceStatus($status) {
		$this->forceStatus = $status === null ? null : (boolean) $status;
		return $this->forceStatus;
	}

	public function setTitle($title = null) {
		$this->title = $title === null ? t($this->name) : sly_translate($title);
		return $this->title;
	}

	public function setPopup($popup) {
		$this->popup = (boolean) $popup;
		return $this->popup;
	}

	public function setPageParam($pageParam) {
		$this->pageParam = $pageParam === null ? $this->name : trim($pageParam);
		return $this->pageParam;
	}

	public function setHidden($hidden) {
		$this->hidden = (boolean) $hidden;
		return $this->hidden;
	}

	public function addSubpage($name, $title = null, $popup = false, $pageParam = null) {
		$subpage = new sly_Layout_Navigation_Subpage($this, $name, $title, $popup, $pageParam);
		return $this->addSubpageObj($subpage);
	}

	public function addSubpageObj(sly_Layout_Navigation_Subpage $subpage) {
		$subpage->setParentPage($this);
		$this->subpages[] = $subpage;
		return $subpage;
	}

	public function addSubpages(array $list) {
		foreach ($list as $sp) {
			if ($sp instanceof sly_Layout_Navigation_Subpage) {
				$this->addSubpageObj($sp);
			}
			else {
				$name      = isset($sp[0]) ? $sp[0] : '';
				$title     = isset($sp[1]) ? $sp[1] : null;
				$popup     = isset($sp[2]) ? $sp[2] : false;
				$pageParam = isset($sp[3]) ? $sp[3] : null;

				$this->addSubpage($name, $title, $popup, $pageParam);
			}
		}
	}

	public function removeSubpage($name) {
		foreach ($this->subpages as $idx => $subpage) {
			if ($subpage->getName() === $name) {
				unset($this->subpages[$idx]);
				$this->subpages = array_values($this->subpages);
				return true;
			}
		}

		return false;
	}
}
