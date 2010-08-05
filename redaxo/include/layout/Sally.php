<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Layout_Sally extends sly_Layout_XHTML
{
	public function __construct()
	{
		global $REX;

		$config = sly_Core::config();

		$this->addCSSFile('media/css/import.css');
		$this->addCSSFile('scaffold/import_export/backend.css');
		$this->addCSSFile('media/css_ie_lte_7.css', 'all', 'if lte IE 7');
		$this->addCSSFile('media/css_ie_7.css', 'all', 'if IE 7');
		$this->addCSSFile('media/css_ie_lte_6.css', 'all', 'if lte IE 6');

		$this->addJavaScriptFile('media/jquery.min.js');
		$this->addJavaScriptFile('media/standard.min.js');

		$this->setTitle($config->get('SERVERNAME').' - ');

		$popups_arr = array('linkmap', 'mediapool');
		$config     = sly_Core::config();

		$body_id = str_replace('_', '-', isset($REX['PAGE']) ? $REX['PAGE'] : '');
		$this->setBodyAttr('id', 'rex-page-'.$body_id);
		$this->setBodyAttr('class', 'sally sally'.$config->get('VERSION').$config->get('SUBVERSION'));

		// Falls ein AddOn bereits in seiner config.inc.php auf das Layout
		// zugegriffen hat, ist $REX['PAGE'] noch nicht bekannt. Wir hängen uns
		// daher in PAGE_CHECKED, um den Wert später noch einmal zu validieren.

		rex_register_extension('PAGE_CHECKED', array($this, 'pageChecked'));

		if (in_array($body_id, $popups_arr)) {
			$this->setBodyAttr('class', 'rex-popup');
		}

		if ($config->get('PAGE_NO_NAVI')) {
			$this->setBodyAttr('onunload', 'closeAll()');
		}

		$this->addHttpMeta('Content-Type', 'text/html charset='.t('htmlcharset'));
	}

	public function pageChecked($params) {
		$body_id = str_replace('_', '-', $params['subject']);
		$this->setBodyAttr('id', 'rex-page-'.$body_id);
	}

	public function printHeader() {
		parent::printHeader();
		$this->renderView('views/layout/sally/top.phtml');
	}

	public function printFooter() {
		$this->renderView('views/layout/sally/bottom.phtml');
		parent::printFooter();
	}

	public function pageHeader($head, $subtitle = null) {
		global $REX;

		if (empty($subtitle)) {
			$subtitle = '<div class="rex-title-row rex-title-row-sub rex-title-row-empty"><p>&nbsp;</p></div>';
		}
		else {
			$subtitle = '<div class="rex-title-row rex-title-row-sub">'.$this->getSubtitle($subtitle).'</div>';
		}

		$this->appendToTitle($head);

		$head = rex_register_extension_point('PAGE_TITLE', $head, array('page' => $REX['PAGE']));
		print '<div id="rex-title"><div class="rex-title-row"><h1>'.$head.'</h1></div>'.$subtitle.'</div>';

		rex_register_extension_point('PAGE_TITLE_SHOWN', $subtitle, array('page' => $REX['PAGE']));
		print '<!-- *** OUTPUT OF CONTENT - START *** --><div id="rex-output">';
	}

	/**
	 * Helper function, die den Subtitle generiert
	 */
	public function getSubtitle($subline, $attr = '')
	{
		global $REX;

		if (empty($subline)) {
			return '';
		}

		$subtitle_str = $subline;
		$subtitle     = $subline;
		$cur_subpage  = sly_request('subpage', 'string');
		$cur_page     = urlencode(sly_request('page', 'string'));

		if (is_array($subline) && !empty($subline)) {
			$subtitle = array();
			$numPages = count($subline);
			$isAdmin  = $REX['USER']->hasPerm('admin[]');

			foreach ($subline as $subpage) {
				if (!is_array($subpage)) {
					continue;
				}

				$link   = $subpage[0];
				$label  = $subpage[1];
				$perm   = !empty($subpage[2]) ? $subpage[2] : '';
				$params = !empty($subpage[3]) ? rex_param_string($subpage[3]) : '';

				// Berechtigung prüfen
				// Hat der User das Recht für die aktuelle Subpage?

				if (!empty($perm) && !$isAdmin && !$REX['USER']->hasPerm($perm)) {
					// Wenn der User kein Recht hat, und diese Seite öffnen will -> Fehler
					if ($cur_subpage == $link) {
						exit('You have no permission to this area!');
					}
					// Den Punkt aus der Navi entfernen
					else {
						continue;
					}
				}

				$link   = reset(explode('&', $link, 2)); // alles nach dem ersten & abschneiden
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
}
