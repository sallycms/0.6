<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
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

	public function __construct($name, $title = null, $popup = false, $pageParam = null) {
		$this->setName($name);
		$this->setTitle($title);
		$this->setPopup($popup);
		$this->setPageParam($pageParam);
		$this->setHidden(false);
		$this->subpages = array();
	}

	public function isActive() {
		global $REX;
		return $REX['PAGE'] == $this->pageParam;
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

	public function setTitle($title = null) {
		$this->title = rex_translate($title === null ? 'translate:'.$this->name : $title);
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
}
