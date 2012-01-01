<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Linkmap extends sly_Controller_Backend {
	protected $globals    = null;
	protected $tree       = array();
	protected $categories = array();
	protected $types      = array();
	protected $roots      = array();
	protected $forced     = array();
	protected $args       = array();
	protected $category   = null;

	public function init() {
		$this->args = sly_requestArray('args', 'string');

		// init category filter
		if (isset($this->args['categories'])) {
			$cats = array_map('intval', explode('|', $this->args['categories']));

			foreach (array_unique($cats) as $catID) {
				$cat = sly_Util_Category::findById($catID);
				if ($cat) $this->categories[] = $catID;
			}
		}

		// init article type filter
		if (isset($this->args['types'])) {
			$types       = explode('|', $this->args['types']);
			$this->types = array_unique($types);
		}

		// generate list of categories that have to be opened (in case we have
		// a deeply nested allow category that would otherwise be unreachable)

		foreach ($this->categories as $catID) {
			if (in_array($catID, $this->forced)) continue;

			$category = sly_Util_Category::findById($catID);
			if (!$category) continue;

			$root = null;

			foreach ($category->getParentTree() as $cat) {
				if ($root === null) $root = $cat->getId();
				$this->forced[] = $cat->getId();
			}

			$this->roots[] = $root;
			$this->forced  = array_unique($this->forced);
			$this->roots   = array_unique($this->roots);
		}

		$catID     = $this->getGlobals('category_id', 0);
		$naviPath  = '<ul class="sly-navi-path">';
		$isRoot    = $catID === 0;
		$category  = $isRoot ? null : sly_Util_Category::findById($catID);

		// respect category filter
		if ($category === null || (!empty($this->categories) && !in_array($category->getId(), $this->forced))) {
			$category = reset($this->categories);
			$category = sly_Util_Category::findById($category);
		}

		$naviPath .= '<li>'.t('path').'</li>';

		if (empty($this->categories) || in_array(0, $this->categories)) {
			$link      = $this->url(array('category_id' => 0));
			$naviPath .= '<li> : <a href="'.$link.'">'.t('home').'</a></li>';
		}
		else {
			$naviPath .= '<li> : <span>'.t('home').'</span></li>';
		}

		if ($category) {
			$root = null;

			foreach ($category->getParentTree() as $cat) {
				$id = $cat->getId();

				$this->tree[]   = $id;
				$this->forced[] = $id;

				if (empty($this->categories) || in_array($id, $this->categories)) {
					$link      = $this->url(array('category_id' => $id));
					$naviPath .= '<li> : <a href="'.$link.'">'.sly_html($cat->getName()).'</a></li>';
				}
				else {
					$naviPath .= '<li> : <span>'.sly_html($cat->getName()).'</span></li>';
				}

				if ($root === null) $root = $id;
			}

			$this->roots[] = $root;
			$this->forced  = array_unique($this->forced);
			$this->roots   = array_unique($this->roots);
		}

		if (empty($this->categories)) {
			$this->roots = sly_Util_Category::getRootCategories();
		}

		$this->category = $category;

		$naviPath .= '</ul>';
		$layout    = sly_Core::getLayout();

		$layout->setBodyAttr('class', 'sly-popup');
		$layout->showNavigation(false);
		$layout->pageHeader(t('linkmap'), $naviPath);
	}

	protected function getGlobals($key = null, $default = null) {
		if ($this->globals === null) {
			$this->globals = array(
				'page'        => 'linkmap',
				'category_id' => sly_request('category_id', 'int'),
				'clang'       => sly_Core::getCurrentClang(),
				'args'        => $this->args
			);
		}

		if ($key !== null) {
			return isset($this->globals[$key]) ? $this->globals[$key] : $default;
		}

		return $this->globals;
	}

	public function indexAction() {
		print $this->render('linkmap/javascript.phtml');
		print $this->render('linkmap/index.phtml');
	}

	public function checkPermission() {
		$user = sly_Util_User::getCurrentUser();
		return !empty($user) && $user->hasStructureRight();
	}

	protected function url($local = array()) {
		return '?'.http_build_query(array_merge($this->getGlobals(), $local), '', '&amp;');
	}

	protected function formatLabel($object) {
		$user  = sly_Util_User::getCurrentUser();
		$label = trim($object->getName());

		if (empty($label)) $label = '&nbsp;';

		if (sly_Util_Article::isValid($object) && !$object->hasType()) {
			$label .= ' ['.t('no_articletype').']';
		}

		return $label;
	}

	protected function tree($children, $level = 1) {
		$ul = '';

		if (is_array($children)) {
			$li  = '';
			$len = count($children);

			foreach ($children as $idx => $cat) {
				if (!($cat instanceof sly_Model_Category)) {
					$cat = sly_Util_Category::findById($cat);
				}

				$cat_children = $cat->getChildren();
				$cat_id       = $cat->getId();
				$classes      = array('lvl-'.$level);
				$sub_li       = '';

				if (!empty($this->categories) && !in_array($cat_id, $this->forced)) {
					continue;
				}

				$classes[] = empty($cat_children) ? 'empty' : 'children';

				if ($idx === 0) {
					$classes[] = 'first';
				}

				if ($idx === $len-1) {
					$classes[] = 'last';
				}

				$hasForcedChildren = false;
				$isVisitable       = empty($this->categories) || in_array($cat_id, $this->categories);

				foreach ($cat_children as $child) {
					if (in_array($child->getId(), $this->forced)) {
						$hasForcedChildren = true;
						break;
					}
				}

				if (in_array($cat_id, $this->tree) || ($hasForcedChildren && !$isVisitable)) {
					$sub_li    = $this->tree($cat_children, $level + 1);
					$classes[] = 'active';

					if ($cat_id == end($this->tree)) {
						$classes[] = 'leaf';
					}
				}

				$classes[] = $cat->isOnline() ? 'sly-online' : 'sly-offline';
				$label     = $this->formatLabel($cat);

				if (!empty($classes)) $classes = ' class="'.implode(' ', $classes).'"';
				else $classes = '';

				$li .= '<li class="lvl-'.$level.'">';

				if ($isVisitable) {
					$li .= '<a'.$classes.' href="'.$this->url(array('category_id' => $cat_id)).'">'.sly_html($label).'</a>';
				}
				else {
					$li .= '<span'.$classes.'>'.sly_html($label).'</span>';
				}

				$li .= $sub_li;
				$li .= '</li>';
			}

			if (!empty($li)) {
				$ul = "<ul>$li</ul>";
			}
		}

		return $ul;
	}
}
