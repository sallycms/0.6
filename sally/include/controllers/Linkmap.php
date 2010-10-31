<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Linkmap extends sly_Controller_Sally {
	protected $globals;
	protected $tree;

	public function init() {
		$catID     = $this->getGlobals('category_id', 0);
		$naviPath = '<ul id="rex-navi-path">';
		$isRoot    = $catID === 0;
		$category  = OOCategory::getCategoryById($catID);
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

		$layout->pageHeader(t('linkmap'), $naviPath);
	}

	protected function getGlobals($key = null, $default = null) {
		if ($this->globals === null) {
			$this->globals = array(
				'page'                    => 'linkmap',
				'HTMLArea'                => sly_request('HTMLArea', 'string'),
				'opener_input_field'      => sly_request('opener_input_field', 'string'),
				'opener_input_field_name' => sly_request('opener_input_field_name', 'string'),
				'category_id'             => sly_request('category_id', 'rex-category-id'),
				'clang'                   => sly_request('clang', 'rex-clang-id')
			);
		}

		if ($key !== null) {
			return isset($this->globals[$key]) ? $this->globals[$key] : $default;
		}

		return $this->globals;
	}

	public function index() {
		$this->render('views/linkmap/javascript.phtml');
		$this->render('views/linkmap/index.phtml');
	}

	public function checkPermission() {
		global $REX;
		return !empty($REX['USER']);
	}

	protected function url($local = array()) {
		return '?'.http_build_query(array_merge($this->getGlobals(), $local), '', '&amp;');
	}

	protected function backlink($id, $name) {
		return sprintf("javascript:insertLink('sally://%d','%s');", $id, addslashes($name));
	}

	protected function formatLabel($object) {
		global $REX;

		$label = trim($object->getName());
		if (empty($label)) $label = '&nbsp;';

		if ($REX['USER']->hasPerm('advancedMode[]')) {
			$label .= ' ['.$object->getId().']';
		}

		if (OOArticle::isValid($object) && !$object->hasTemplate()) {
			$label .= ' ['.t('lmap_has_no_template').']';
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