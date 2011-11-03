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
class sly_Layout_Backend extends sly_Layout_XHTML {
	private $hasNavigation = true;
	private $navigation;

	public function __construct() {
		$config = sly_Core::config();

		$this->addCSSFile('assets/css/import.css');

		$this->addJavaScriptFile('assets/js/jquery.min.js');
		$this->addJavaScriptFile('assets/js/standard.min.js');
		$this->addJavaScriptFile('assets/js/modernizr.min.js');

		$this->setTitle(sly_Core::getProjectName().' - ');

		$config = sly_Core::config();
		$this->addMeta('robots', 'noindex,nofollow');
		$this->setBase(sly_Util_HTTP::getBaseUrl(true).'/backend/');

		$locale = explode('_', sly_Core::getI18N()->getLocale(), 2);
		$locale = reset($locale);

		if (strlen($locale) === 2) {
			$this->setLanguage(strtolower($locale));
		}

		sly_Core::dispatcher()->register('PAGE_CHECKED', array($this, 'pageChecked'));
	}

	public function pageChecked(array $params) {
		$page = $params['subject'];

		$body_id = str_replace('_', '-', $page);
		$this->setBodyAttr('id', 'rex-page-'.$body_id);
	}

	public function printHeader() {
		parent::printHeader();
		print $this->renderView('layout/sally/top.phtml');
	}

	public function printFooter() {
		print $this->renderView('layout/sally/bottom.phtml');
		parent::printFooter();
	}

	public function pageHeader($head, $subtitle = '') {
		if (!empty($subtitle)) {
			$subtitle = '<div class="pagehead-row">'.$this->getSubtitle($subtitle).'</div>';
		}
		else {
			$subtitle = '';
		}

		$this->appendToTitle($head);
		$dispatcher = sly_Core::dispatcher();

		$page = sly_Core::getCurrentPage();
		$head = $dispatcher->filter('PAGE_TITLE', $head, compact('page'));
		print '<div id="sly-pagehead"><div class="pagehead-row"><h1>'.$head.'</h1></div>'.$subtitle.'</div>';

		$dispatcher->notify('PAGE_TITLE_SHOWN', $subtitle, compact('page'));
	}

	/**
	 * Helper function, die den Subtitle generiert
	 */
	public function getSubtitle($subline, $attr = '') {
		if (empty($subline)) {
			return '';
		}

		$subtitle_str = $subline;
		$subtitle     = $subline;
		$cur_page     = urlencode(sly_request('page', 'string'));
		$user         = sly_Util_User::getCurrentUser();

		if (is_array($subline) && !empty($subline)) {
			$subtitle = array();
			$numPages = count($subline);
			$isAdmin  = $user->isAdmin();

			foreach ($subline as $subpage) {
				if (!is_array($subpage)) {
					continue;
				}

				$page     = $subpage[0];
				$label    = $subpage[1];
				$params   = !empty($subpage[3]) ? sly_Util_HTTP::queryString($subpage[3]) : '';
				$pageattr = $attr;

				
				if($cur_page === $page) {
					$pageattr = $attr.' class="rex-active"';
				}
				$format     = '<a href="?page=%s%s"%s>%s</a>';
				$subtitle[] = sprintf($format, $page, $params, $pageattr, $label);
			}

			if (!empty($subtitle)) {
				$items = array();

				foreach ($subtitle as $idx => $part) {
					$className = isset($subline[$idx][4]) ? $subline[$idx][4] : '';

					if ($idx == 0) {
						$items[] = '<li class="'.trim("rex-navi-first $className").'">'.$part.'</li>';
					}
					else {
						$items[] = '<li'.($className ? ' class="'.$className.'"' : '').'>'.$part.'</li>';
					}
				}

				$subtitle_str = '<div id="rex-navi-page"><ul>'.implode("\n", $items).'</ul></div>';
			}
		}

		return $subtitle_str;
	}

	/**
	 * override default hasNavigation value
	 *
	 * @param boolean $active true to show navigation falso to hide
	 */
	public function showNavigation($active = true) {
		$this->hasNavigation = $active;
	}

	public function hasNavigation() {
		return $this->hasNavigation;
	}
	
	/**
	 * @return sly_Layout_Navigation_Backend
	 */
	public function getNavigation() {
		if (!isset($this->navigation)) {
			$this->navigation = new sly_Layout_Navigation_Backend();
		}

		return $this->navigation;
	}

	protected function getViewFile($file) {
		$full = SLY_SALLYFOLDER.'/backend/views/'.$file;
		if (file_exists($full)) return $full;

		return parent::getViewFile($file);
	}
}
