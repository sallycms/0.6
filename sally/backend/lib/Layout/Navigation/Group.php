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
class sly_Layout_Navigation_Group {
	private $name;
	private $title;
	private $pages;

	public function __construct($name, $title) {
		$this->name  = trim($name);
		$this->title = rex_translate(trim($title));
		$this->pages = array();
	}

	public function getName()  { return $this->name;  }
	public function getTitle() { return $this->title; }
	public function getPages() { return $this->pages; }

	public function addPage(sly_Layout_Navigation_Page $page) {
		$this->pages[] = $page;
	}

	public function sortPages() {
		usort($this->pages, array(__CLASS__, 'comparePages'));
	}

	public static function comparePages(sly_Layout_Navigation_Page $pageA, sly_Layout_Navigation_Page $pageB) {
		return strnatcasecmp($pageA->getTitle(), $pageB->getTitle());
	}

	public function removePage($name) {
		foreach ($this->pages as $idx => $page) {
			if ($page->getName() === $name) {
				unset($this->pages[$idx]);
				$this->pages = array_values($this->pages);
				return true;
			}
		}

		return false;
	}
}
