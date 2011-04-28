<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * URL Funktionen
 *
 * @package redaxo4
 */

/**
 * Baut einen Parameter String anhand des array $params
 *
 * @param  mixed  $params   array or string
 * @param  string $divider  only used if $params is array
 * @return string
 */
function rex_param_string($params, $divider = '&amp;') {
	if (!empty($params)) {
		if (is_array($params)) {
			return $divider.http_build_query($params, '', $divider);
		}
		else {
			return $params;
		}
	}

	return '';
}

/**
 * Gibt eine Url zu einem Artikel zurück
 *
 * @param  int     $id            ID des Artikels
 * @param  int     $clang         Sprache des Artikels (false = aktuelle Sprache)
 * @param  string  $name          Artikelname (unbenutzt seit Sally 0.4)
 * @param  array   $params        Array von Parametern
 * @param  string  $divider       Trennzeichen für Parameter (z.B. &amp; für HTML, & für Javascript)
 * @param  boolean $disableCache  schaltet das URL-Caching ab
 * @return string
 */
function rex_getUrl($id = 0, $clang = false, $name = 'NoName', $params = '', $divider = '&amp;', $disableCache = false) {
	static $urlCache = array();

	$clangOrig    = $clang;
	$id           = (int) $id;
	$clang        = (int) $clang;
	$multilingual = sly_Util_Language::isMultilingual();
	$dispatcher   = sly_Core::dispatcher();
	$config       = sly_Core::config();

	if ($id <= 0) {
		$id = sly_Core::getCurrentArticleId();
	}

	// Wenn eine rexExtension vorhanden ist, immer die clang mitgeben!
	// Die rexExtension muss selbst entscheiden was sie damit macht.

	if ($clangOrig === false && ($multilingual || $dispatcher->hasListeners('URL_REWRITE'))) {
		$clang = sly_Core::getCurrentClang();
	}

	// Die Erzeugung von URLs kann in Abhängigkeit von den installierten
	// AddOns eine ganze Weile dauern. Da sich die URLs auf einer Seite
	// wohl eher selten ändern, cachen wir sie hier zwischen.

	$func     = function_exists('json_encode') ? 'json_encode' : 'serialize';
	$cacheKey = substr(md5($id.'_'.$clang.'_'.$func($params).'_'.$divider), 0, 10); // $params kann ein Array sein.

	if (!$disableCache && isset($urlCache[$cacheKey])) {
		return $urlCache[$cacheKey];
	}

	// Listener nach der zu verwendenden URL fragen

	$paramString = rex_param_string($params, $divider);
	$url         = $dispatcher->filter('URL_REWRITE', '', array(
		'id'            => $id,
		'name'          => $name,
		'clang'         => $clang,
		'params'        => $paramString,
		'divider'       => $divider,
		'disable_cache' => $disableCache
	));

	// Wenn kein Listener eine URL erzeugt hat, erzeugen wir eine primitive
	// eigene in der Form index.php?article_id=...

	if (empty($url)) {
		$clangString = '';

		if ($multilingual && $clang != $config->get('START_CLANG_ID')) {
			$clangString = $divider.'clang='.$clang;
		}

		$url = $config->get('FRONTEND_FILE').'?article_id='.$id.$clangString.$paramString;
	}

	$urlCache[$cacheKey] = $url;
	return $url;
}
