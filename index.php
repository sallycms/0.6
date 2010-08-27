<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
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

$REX['REDAXO']      = false; // Backend = true, Frontend = false
$REX['SALLY']       = false; // Backend = true, Frontend = false
$REX['HTDOCS_PATH'] = './';

// Core laden

require_once 'sally/include/master.inc.php';
require_once 'sally/include/addons.inc.php';

// Setup?
if ($config->get('SETUP')) {
	header('Location: sally/index.php');
	exit('Bitte führe das <a href="sally/index.php">Setup</a> aus, um SallyCMS zu nutzen.');
}

// Aktuellen Artikel finden und ausgeben

$REX['ARTICLE'] = new rex_article();
$REX['ARTICLE']->setCLang(sly_Core::getCurrentClang());

if ($REX['ARTICLE']->setArticleId(sly_Core::getCurrentArticleId())) {
	print $REX['ARTICLE']->getArticleTemplate();
}
else {
	print 'Kein Startartikel selektiert. Bitte setze ihn im <a href="sally/index.php">Backend</a>.';
	$REX['STATS']   = 0;
	$REX['ARTICLE'] = null;
}

$content = ob_get_clean();
rex_send_article($REX['ARTICLE'], $content, 'frontend');
