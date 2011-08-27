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

		if ($user->hasRight('advancedMode[]')) {
			$label .= ' ['.$object->getId().']';
		}

		if (sly_Util_Article::isValid($object) && !$object->hasType()) {
			$label .= ' ['.t('lmap_has_no_type').']';
		}

		return $label;
	}

	protected function tree($children) {
		$ul = '';

		if (is_array($children)) {
			$li = '';

			foreach ($children as $cat) {
				$cat_children = $cat->getChildren();
				$cat_id       = $cat->getId();
				$liclasses    = array();
				$linkclasses  = array();
				$sub_li       = '';

				if (!empty($cat_children)) {
					$liclasses[]   = 'rex-children';
					$linkclasses[] = 'rex-linkmap-is-not-empty';
				}

				if (next($children) == null) {
					$liclasses[] = 'rex-children-last';
				}

				$linkclasses[] = $cat->isOnline() ? 'rex-online' : 'rex-offline';

				if (in_array($cat_id, $this->tree)) {
					$sub_li        = $this->tree($cat_children);
					$liclasses[]   = 'rex-active';
					$linkclasses[] = 'rex-active';
				}

				if (!empty($liclasses)) $liclasses = ' class="'.implode(' ', $liclasses).'"';
				else $liclasses = '';

				if (!empty($linkclasses)) $linkclasses = ' class="'.implode(' ', $linkclasses).'"';
				else $linkclasses = '';

				$label = $this->formatLabel($cat);

				$li .= '<li'.$liclasses.'>';
				$li .= '<a'.$linkclasses.' href="'.$this->url(array('category_id' => $cat_id)).'">'.sly_html($label).'</a>';
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
