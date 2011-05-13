<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * HTTP1.1 Client Cache Features
 *
 * @package redaxo4
 */

/**
 * Sendet eine Datei zum Client
 *
 * @param string $file         Pfad zur Datei
 * @param string $contentType  ContentType der Datei
 * @param string $environment  Die Umgebung aus der der Inhalt gesendet wird (frontend/backend)
 */
function rex_send_file($file, $contentType, $environment = 'backend') {
	global $REX;

	// Cachen für Dateien aktivieren
	$temp = $REX['USE_LAST_MODIFIED'];
	$REX['USE_LAST_MODIFIED'] = true;

	header('Content-Type: '.$contentType);
	header('Content-Disposition: inline; filename="'.basename($file).'"');

	$content  = file_get_contents($file);
	$cacheKey = md5($content.$file.$contentType.$environment);

	rex_send_content($content, filemtime($file), $cacheKey, $environment);

	// Setting zurücksetzen
	$REX['USE_LAST_MODIFIED'] = $temp;
}

/**
 * Sendet einen sly_Model_Article zum Client,
 * fügt ggf. HTTP1.1 cache headers hinzu
 *
 * @param sly_Model_Article $article      der zu sendene Artikel
 * @param string            $content      Inhalt des Artikels
 * @param string            $environment  die Umgebung aus der der Inhalt gesendet wird (frontend/backend)
 */
function rex_send_article($article, $content, $environment) {
	$config  = sly_Core::config();
	$content = sly_Core::dispatcher()->filter('OUTPUT_FILTER', $content, array('environment' => $environment));

	// keine Manipulation der Ausgaben ab hier
	sly_Core::dispatcher()->notify('OUTPUT_FILTER_CACHE', $content, '', true);

	if ($article) {
		$lastModified = $article->getUpdateDate();
		$requestedID  = sly_request('article_id', 'int');
		$notFoundID   = $config->get('NOTFOUND_ARTICLE_ID');

		if ($requestedID != $notFoundID && $article->getId() == $notFoundID && $article->getId() != $config->get('START_ARTICLE_ID')) {
			header('HTTP/1.0 404 Not Found');
		}
	}
	else {
		$lastModified = time();
	}

	$etag = substr(md5($content), 0, 12);
	rex_send_content(trim($content), $lastModified, $etag, $environment);
}

/**
 * Sendet den Content zum Client
 *
 * fügt ggf. HTTP1.1 Cache Header hinzu
 *
 * @param string $content       Inhalt des Artikels
 * @param int    $lastModified  Last-Modified Timestamp
 * @param string $etag          Cachekey zur Identifizierung des Caches
 * @param string $environment   die Umgebung aus der der Inhalt gesendet wird (frontend/backend)
 */
function rex_send_content($content, $lastModified, $etag, $environment) {
	$config  = sly_Core::config();
	$lastMod = $config->get('USE_LAST_MODIFIED');
	$useEtag = $config->get('USE_ETAG');
	$md5     = $config->get('USE_MD5');

	if ($lastMod === true || $lastMod == $environment) {
		rex_send_last_modified($lastModified);
	}

	if ($useEtag === true || $useEtag == $environment) {
		rex_send_etag($etag);
	}

	if (!sly_ini_get('zlib.output_compression')) {
		if (ob_start('ob_gzhandler') === false) {
			// manually send content length if everything fails
			header('Content-Length: '.strlen($content));
		}
	}

	print $content;
}

/**
 * Prüft, ob sich dateien geändert haben
 *
 * XHTML 1.1: HTTP_IF_MODIFIED_SINCE feature
 *
 * @param int $lastModified  Last-Modified Timestamp
 */
function rex_send_last_modified($lastModified = null) {
	if (!$lastModified) {
		$lastModified = time();
	}

	$lastModified = date('r', $lastModified);

	// Sende Last-Modification time
	header('Last-Modified: ' .$lastModified);

	// Last-Modified Timestamp gefunden
	// => den Browser anweisen, den Cache zu verwenden
	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lastModified) {
		while (ob_get_level()) ob_end_clean();
		header('HTTP/1.1 304 Not Modified');
		exit();
	}
}

/**
 * Prüft ob sich der Inhalt einer Seite im Cache des Browsers befindet und
 * verweisst ggf. auf den Cache
 *
 * XHTML 1.1: HTTP_IF_NONE_MATCH feature
 *
 * @param string $cacheKey  Cachekey zur identifizierung des Caches
 */
function rex_send_etag($cacheKey) {
	// Laut HTTP Spec muss der Etag in " sein
	$cacheKey = '"'.$cacheKey.'"';

	// Sende CacheKey als ETag
	header('ETag: '.$cacheKey);

	// CacheKey gefunden
	// => den Browser anweisen, den Cache zu verwenden
	if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $cacheKey) {
		while (ob_get_level()) ob_end_clean();
		header('HTTP/1.1 304 Not Modified');
		exit();
	}
}
