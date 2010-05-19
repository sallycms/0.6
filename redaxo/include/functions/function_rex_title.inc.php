<?php

/**
 * Funktionen zur Ausgabe der Titel Leiste und Subnavigation
 * @package redaxo4
 * @version svn:$Id$
 */

/**
 * Ausgabe des Seitentitels
 *
 *
 * Beispiel für einen Seitentitel
 *
 * <code>
 * $subpages = array(
 *  array( ''      , 'Index'),
 *  array( 'lang'  , 'Sprachen'),
 *  array( 'groups', 'Gruppen')
 * );
 *
 * rex_title( 'Headline', $subpages)
 * </code>
 *
 *
 * Beispiel für einen Seitentitel mit Rechteprüfung
 *
 * <code>
 * $subpages = array(
 *  array( ''      , 'Index'   , 'index_perm'),
 *  array( 'lang'  , 'Sprachen', 'lang_perm'),
 *  array( 'groups', 'Gruppen' , 'group_perm')
 * );
 *
 * rex_title( 'Headline', $subpages)
 * </code>
 *
 *
 * Beispiel für einen Seitentitel eigenen Parametern
 *
 * <code>
 * $subpages = array(
 *  array( ''      , 'Index'   , '', array('a' => 'b')),
 *  array( 'lang'  , 'Sprachen', '', 'a=z&x=12'),
 *  array( 'groups', 'Gruppen' , '', array('clang' => $REX['CUR_CLANG']))
 * );
 *
 * rex_title( 'Headline', $subpages)
 * </code>
 */
function rex_title($head, $subtitle = '')
{
	global $article_id, $category_id, $page;

	if (empty($subtitle)) {
		$subtitle = '<div class="rex-title-row rex-title-row-sub rex-title-row-empty"><p>&nbsp;</p></div>';
	}
	else {
		$subtitle = '<div class="rex-title-row rex-title-row-sub">'.rex_get_subtitle($subtitle).'</div>';
	}

	$head = rex_register_extension_point('PAGE_TITLE', $head, array(
		'category_id' => $category_id,
		'article_id'  => $article_id,
		'page'        => $page
	));

	print '
<div id="rex-title">
	<div class="rex-title-row"><h1>'.$head.'</h1></div>
	'.$subtitle.'
</div>';

	rex_register_extension_point('PAGE_TITLE_SHOWN', $subtitle, array(
		'category_id' => $category_id,
		'article_id'  => $article_id,
		'page'        => $page
	));

	print '
<!-- *** OUTPUT OF CONTENT - START *** -->
<div id="rex-output">
';
}

/**
 * Helper function, die den Subtitle generiert
 */
function rex_get_subtitle($subline, $attr = '')
{
	global $REX;

	if (empty($subline)) {
		return  '';
	}

	$subtitle_str = $subline;
	$subtitle     = $subline;
	$cur_subpage  = rex_request('subpage', 'string');
	$cur_page     = rex_request('page', 'string');

	if (is_array($subline) && count($subline) > 0) {
		$subtitle = array();
		$numPages = count($subline);

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
			
			if (!empty($perm) && !$REX['USER']->hasPerm('admin[]') && !$REX['USER']->hasPerm($perm)) {
				// Wenn der User kein Recht hat, und diese Seite öffnen will -> Fehler
				if ($cur_subpage == $link) {
					exit('You have no permission to this area!');
				}
				// Den Punkt aus der Navi entfernen
				else {
					continue;
				}
			}

			// Falls im Link parameter enthalten sind, diese Abschneiden
			if (($pos = strpos($link, '&')) !== false) {
				$link = substr($link, 0, $pos);
			}

			$active = (empty($cur_subpage) && empty($link)) || (!empty($cur_subpage) && $cur_subpage == $link);

			// Auf der aktiven Seite den Link nicht anzeigen
			if ($active) {
				$link       = empty($link) ? '' : '&amp;subpage='.$link;
				$format     = '<a href="?page='.$cur_page.'%s%s"%s class="rex-active">%s</a>';
				$subtitle[] = sprintf($format, $link, $params, $attr, $label);
			}
			elseif (empty($link)) {
				$format     = '<a href="?page='.$cur_page.'%s"%s>%s</a>';
				$subtitle[] = sprintf($format, $params, $attr, $label);
			}
			else {
				$link       = empty($link) ? '' : '&amp;subpage='.$link;
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
			
			$subtitle_str = '
<div id="rex-navi-page">
	<ul>
		'.implode("\n", $items).'
	</ul>
</div>
';
		}
	}
	
	return $subtitle_str;
}
