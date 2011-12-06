<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Linkmap extends sly_Controller_Backend {
	protected $globals;
	protected $tree;

	public function init() {
		$catID     = $this->getGlobals('category_id', 0);
		$naviPath  = '<ul class="sly-navi-path">';
		$isRoot    = $catID === 0;
		$category  = sly_Util_Category::findById($catID);
		$link      = $this->url(array('category_id' => 0));

		$naviPath .= '<li>'.t('path').' </li>';
		$naviPath .= '<li>: <a href="'.$link.'">'.t('homepage').'</a> </li>';

		$this->tree = array();

		if ($category) {
			foreach ($category->getParentTree() as $cat) {
				$this->tree[] = $cat->getId();
				$link         = $this->url(array('category_id' => $cat->getId()));
				$naviPath    .= '<li> : <a href="'.$link.'">'.sly_html($cat->getName()).'</a></li>';
			}
		}

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
				'category_id' => sly_request('category_id', 'rex-category-id'),
				'clang'       => sly_request('clang', 'rex-clang-id')
			);
		}

		if ($key !== null) {
			return isset($this->globals[$key]) ? $this->globals[$key] : $default;
		}

		return $this->globals;
	}

	public function index() {
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
			$label .= ' ['.t('lmap_has_no_type').']';
		}

		return $label;
	}

	protected function tree($children, $level = 1) {
		$ul = '';

		if (is_array($children)) {
			$li  = '';
			$len = count($children);

			foreach ($children as $idx => $cat) {
				$cat_children = $cat->getChildren();
				$cat_id       = $cat->getId();
				$classes      = array('lvl-'.$level);
				$sub_li       = '';

				$classes[] = empty($cat_children) ? 'empty' : 'children';

				if ($idx === 0) {
					$classes[] = 'first';
				}

				if ($idx === $len-1) {
					$classes[] = 'last';
				}

				if (in_array($cat_id, $this->tree)) {
					$sub_li    = $this->tree($cat_children, $level + 1);
					$classes[] = 'active';

					if ($cat_id == end($this->tree)) {
						$classes[] = 'leaf';
					}
				}

				$classes[] = $cat->isOnline() ? 'rex-online' : 'rex-offline';
				$label     = $this->formatLabel($cat);

				if (!empty($classes)) $classes = ' class="'.implode(' ', $classes).'"';
				else $classes = '';

				$li .= '<li class="lvl-'.$level.'">';
				$li .= '<a'.$classes.' href="'.$this->url(array('category_id' => $cat_id)).'">'.sly_html($label).'</a>';
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
