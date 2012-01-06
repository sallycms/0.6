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
	private $extraParams;

	public function __construct(sly_Layout_Navigation_Page $parent, $name, $title = null, $popup = false, $pageParam = null) {
		$this->setName($name);
		$this->setTitle($title);
		$this->setPopup($popup);
		$this->setPageParam($pageParam);
		$this->setParentPage($parent);
		$this->forceStatus(null);
		$this->setExtraParams(array());
	}

	public function getName()         { return $this->name;        }
	public function getTitle()        { return $this->title;       }
	public function isPopup()         { return $this->popup;       }
	public function getPageParam()    { return $this->pageParam;   }
	public function getExtraParams()  { return $this->extraParams; }
	public function getParentPage()   { return $this->parent;      }
	public function getForcedStatus() { return $this->forceStatus; }

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

	public function setExtraParams(array $extraParams) {
		$this->extraParams = $extraParams;
		return $this->extraParams;
	}

	public function setParentPage(sly_Layout_Navigation_Page $parent) {
		$this->parent = $parent;
	}

	public function isActive() {
		$forced = $this->forceStatus;
		if ($forced !== null) return $forced;

		$current = sly_Core::getCurrentControllerName();
		return $this->matches($current, $_REQUEST);
	}

	public function matches($subpagePageParam, array $extraParams = array()) {
		if ($subpagePageParam !== $this->pageParam) return false;

		// check if all extra params match
		foreach ($this->extraParams as $key => $value) {
			// allow type coercing here
			if (!isset($extraParams[$key]) || $extraParams[$key] != $value) {
				return false;
			}
		}

		return true;
	}
}
