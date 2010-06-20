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
 *
 * @deprecated  sly_Layout_HTML::pageHeader() ist die korrekte Variante.
 */
function rex_title($head, $subtitle = '') {
	$layout = sly_Core::getLayout('XHTML');
	$layout->pageHeader($head, $subtitle);
}

/**
 * Helper function, die den Subtitle generiert
 *
 * @deprecated  sly_Layout_HTML::getSubtitle() ist die korrekte Variante.
 */
function rex_get_subtitle($subline, $attr = '') {
	$layout = sly_Core::getLayout('XHTML');
	return $layout->getSubtitle($subline, $attr);
}
