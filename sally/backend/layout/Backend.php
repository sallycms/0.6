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

	public function __construct() {
		global $REX;

		$config = sly_Core::config();

		$this->addCSSFile('assets/css/import.css');

		$this->addJavaScriptFile('assets/js/jquery.min.js');
		$this->addJavaScriptFile('assets/js/standard.min.js');

		$this->setTitle($config->get('SERVERNAME').' - ');

		$config = sly_Core::config();
		$this->setBodyAttr('class', 'sally sally'.$config->get('VERSION').$config->get('SUBVERSION'));

		// Falls ein AddOn bereits in seiner config.inc.php auf das Layout
		// zugegriffen hat, ist $REX['PAGE'] noch nicht bekannt. Wir hängen uns
		// daher in PAGE_CHECKED, um den Wert später noch einmal zu validieren.

		$this->pageChecked(array('subject' => isset($REX['PAGE']) ? $REX['PAGE'] : ''));
		sly_Core::dispatcher()->register('PAGE_CHECKED', array($this, 'pageChecked'));

		$this->addHttpMeta('Content-Type', 'text/html; charset='.t('htmlcharset'));
		$this->addMeta('robots', 'noindex,nofollow');
	}

	public function pageChecked($params) {
		$body_id = str_replace('_', '-', $params['subject']);
		$this->setBodyAttr('id', 'rex-page-'.$body_id);

		$popups_arr = array('linkmap', 'mediapool');

		if (in_array($body_id, $popups_arr)) {
			$this->setBodyAttr('class', 'rex-popup');
		}

		$active = sly_Core::getNavigation()->getActivePage();

		if ($active && $active->isPopup()) {
			$this->setBodyAttr('onunload', 'closeAll()');
		}
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
		global $REX;

		if (!empty($subtitle)) {
			$subtitle = '<div class="pagehead-row">'.$this->getSubtitle($subtitle).'</div>';
		}

		$this->appendToTitle($head);
		$dispatcher = sly_Core::dispatcher();

		$head = $dispatcher->filter('PAGE_TITLE', $head, array('page' => $REX['PAGE']));
		print '<div id="sly-pagehead"><div class="pagehead-row"><h1>'.$head.'</h1></div>'.$subtitle.'</div>';

		$dispatcher->notify('PAGE_TITLE_SHOWN', $subtitle, array('page' => $REX['PAGE']));
		print '<!-- *** OUTPUT OF CONTENT - START *** -->';
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
		$cur_subpage  = sly_request('subpage', 'string');
		$cur_page     = urlencode(sly_request('page', 'string'));
		$user         = sly_Util_User::getCurrentUser();

		if (is_array($subline) && !empty($subline)) {
			$subtitle = array();
			$numPages = count($subline);
			$isAdmin  = $user->hasPerm('admin[]');

			foreach ($subline as $subpage) {
				if (!is_array($subpage)) {
					continue;
				}

				$link   = $subpage[0];
				$label  = $subpage[1];
				$perm   = !empty($subpage[2]) ? $subpage[2] : '';
				$params = !empty($subpage[3]) ? sly_Util_HTTP::queryString($subpage[3]) : '';

				// Berechtigung prüfen
				// Hat der User das Recht für die aktuelle Subpage?

				if (!empty($perm) && !$isAdmin && !$user->hasPerm($perm)) {
					// Wenn der User kein Recht hat, und diese Seite öffnen will -> Fehler
					if ($cur_subpage == $link) {
						exit('You have no permission to this area!');
					}
					// Den Punkt aus der Navi entfernen
					else {
						continue;
					}
				}

				$link   = explode('&', $link, 2);
				$link   = reset($link); // alles nach dem ersten & abschneiden
				$active = (empty($cur_subpage) && empty($link)) || (!empty($cur_subpage) && $cur_subpage == $link);

				// Auf der aktiven Seite den Link nicht anzeigen
				if ($active) {
					$link       = empty($link) ? '' : '&amp;subpage='.urlencode($link);
					$format     = '<a href="?page='.$cur_page.'%s%s"%s class="rex-active">%s</a>';
					$subtitle[] = sprintf($format, $link, $params, $attr, $label);
				}
				elseif (empty($link)) {
					$format     = '<a href="?page='.$cur_page.'%s"%s>%s</a>';
					$subtitle[] = sprintf($format, $params, $attr, $label);
				}
				else {
					$link       = '&amp;subpage='.urlencode($link);
					$format     = '<a href="?page='.$cur_page.'%s%s"%s>%s</a>';
					$subtitle[] = sprintf($format, $link, $params, $attr, $label);
				}
			}

			if (!empty($subtitle)) {
				$items = array();
				$i     = 1;

				foreach ($subtitle as $part) {
					if ($i == 1) {
						$items[] = '<li class="rex-navi-first">'.$part.'</li>';
					}
					else {
						$items[] = '<li>'.$part.'</li>';
					}

					++$i;
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

	protected function getViewFile($file) {
		$full = SLY_SALLYFOLDER.'/backend/views/'.$file;
		if (file_exists($full)) return $full;

		return parent::getViewFile($file);
	}
}
