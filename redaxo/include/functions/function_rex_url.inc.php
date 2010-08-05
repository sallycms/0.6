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

function rex_parse_article_name($name)
{
	static $search = null, $replace = null;

	if ($search === null || $replace === null) {
		global $REX, $I18N;

		// Im Frontend gibts kein I18N

		if (!$I18N) {
			$I18N = rex_create_lang($REX['LANG']);
		}

		// sprachspezifische Sonderzeichen filtern

		$search  = explode('|', $I18N->msg('special_chars'));
		$replace = explode('|', $I18N->msg('special_chars_rewrite'));
	}

	return
		// ggf übrige zeichen url-codieren
		urlencode(
			// mehrfach hintereinander auftretende Spaces auf eines reduzieren
			preg_replace('/ {2,}/',' ',
				// alle sonderzeichen raus
				preg_replace('/[^a-zA-Z_\-0-9 ]/', '',
					// sprachspezifische Zeichen umschreiben
					str_replace($search, $replace, $name)
			)
		)
	);
}

/**
 * Baut einen Parameter String anhand des array $params
 */
function rex_param_string($params, $divider = '&amp;')
{
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
 * @param [$_id] ArtikelId des Artikels
 * @param [$_clang] SprachId des Artikels
 * @param [$_params] Array von Parametern
 * @param [$_divider] Trennzeichen für Parameter
 * (z.B. &amp; für HTML, & für Javascript)
 */
function rex_getUrl($id = 0, $clang = false, $name = 'NoName', $params = '', $divider = '&amp;')
{
	global $REX;

	$clangOrig = $clang;
	$id        = (int) $id;
	$clang     = (int) $clang;

	if ($id <= 0) {
		$id = sly_Core::getCurrentArticleId();
	}

	// Wenn eine rexExtension vorhanden ist, immer die clang mitgeben!
	// Die rexExtension muss selbst entscheiden was sie damit macht.

	if ($clangOrig === false && (rex_is_multilingual() || rex_extension_is_registered('URL_REWRITE'))) {
		$clang = rex_cur_clang();
	}

	// Die Erzeugung von URLs kann in Abhängigkeit von den installierten
	// AddOns eine ganze Weile dauern. Da sich die URLs auf einer Seite
	// wohl eher selten ändern, cachen wir sie hier zwischen.

	static $urlCache = array();

	$func     = function_exists('json_encode') ? 'json_encode' : 'serialize';
	$cacheKey = substr(md5($id.'_'.$clang.'_'.$func($params).'_'.$divider), 0, 10); // $params kann ein Array sein.

	if (isset($urlCache[$cacheKey])) {
		return $urlCache[$cacheKey];
	}

	$paramString = rex_param_string($params, $divider);

	if ($id != 0) {
		$ooa = OOArticle::getArticleById($id, $clang);
		if ($ooa) {
			$name = rex_parse_article_name($ooa->getName());
		}
	}

	$url = rex_register_extension_point('URL_REWRITE', '', array(
		'id'      => $id,
		'name'    => $name,
		'clang'   => $clang,
		'params'  => $paramString,
		'divider' => $divider
	));

	if (empty($url)) {
		if ($REX['MOD_REWRITE'] === true || $REX['MOD_REWRITE'] == 'true') {
			$rewrite_fn = 'rex_apache_rewrite';
		}
		else {
			$rewrite_fn = 'rex_no_rewrite';
		}

		$url = call_user_func($rewrite_fn, $id, $name, $clang, $paramString, $divider);
	}

	$urlCache[$cacheKey] = $url;
	return $url;
}

// ----------------------------------------- Rewrite functions

/**
 * Standard Rewriter, gibt normale Urls zurück im Format
 * index.php?article_id=$article_id[&clang=$clang&$params]
 */
function rex_no_rewrite($id, $name, $clang, $param_string, $divider)
{
	global $REX;
	$clangString = '';

	if (rex_is_multilingual()) {
		$clangString = $divider.'clang='.$clang;
	}

	return $REX['FRONTEND_FILE'].'?article_id='.$id.$clangString.$param_string;
}

/**
 * Standard Rewriter, gibt umschriebene URLs im Format
 *
 * <id>-<clang>-<name>.html[?<params>]
 *
 * zurück.
 */
function rex_apache_rewrite($id, $name, $clang, $params, $divider)
{
	if (!empty($params)) {
		// strip first "&"
		$params = '?'.substr($params, strpos($params, $divider) + strlen($divider));
	}

	return $id.'-'.$clang.'-'.$name.'.html'.$params;
}
