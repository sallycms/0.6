<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup layout
 */
class sly_Layout_Navigation_Subpage {
	private $name;
	private $title;
	private $popup;
	private $pageParam;
	private $parent;
	private $forceStatus;

	public function __construct(sly_Layout_Navigation_Page $parent, $name, $title = null, $popup = false, $pageParam = null) {
		$this->setName($name);
		$this->setTitle($title);
		$this->setPopup($popup);
		$this->setPageParam($pageParam);
		$this->setParentPage($parent);
		$this->forceStatus(null);
	}

	public function getName()       { return $this->name;      }
	public function getTitle()      { return $this->title;     }
	public function isPopup()       { return $this->popup;     }
	public function getPageParam()  { return $this->pageParam; }
	public function getParentPage() { return $this->parent;    }

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

	public function setParentPage(sly_Layout_Navigation_Page $parent) {
		$this->parent = $parent;
	}

	public function isActive() {
		$forced    = $this->forceStatus;
		$isSubpage = sly_request('page', 'string') == $this->pageParam;
		if ($forced !== null) return $forced;

		return $isSubpage;
	}
}
