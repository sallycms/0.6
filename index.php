<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

define('IS_SALLY', true);
define('IS_SALLY_BACKEND', false);

if (!defined('SLY_IS_TESTING')) define('SLY_IS_TESTING', false);

ob_start();
ob_implicit_flush(0);

// $REX vorbereiten

unset($REX);

define('SLY_HTDOCS_PATH', './');

// Core laden

require_once 'sally/include/master.inc.php';

sly_Core::loadAddons();

if ($config->get('FRONTEND_SYNC')) {
	require_once 'sally/include/functions/function_rex_generate.inc.php';
	sly_Service_Factory::getService('Template')->refresh();
	sly_Service_Factory::getService('Module')->refresh();
}

// Setup?
if ($config->get('SETUP')) {
	header('Location: sally/index.php');
	exit('Bitte führe das <a href="sally/index.php">Setup</a> aus, um SallyCMS zu nutzen.');
}

// Aktuellen Artikel finden und ausgeben

$article = OOArticle::getArticleById(sly_Core::getCurrentArticleId(), sly_Core::getCurrentClang());

if ($article) {
	print $article->getArticleTemplate();
}
else {
	print 'Kein Startartikel selektiert. Bitte setze ihn im <a href="sally/index.php">Backend</a>.';
}

$content = ob_get_clean();
rex_send_article($article, $content, 'frontend');
