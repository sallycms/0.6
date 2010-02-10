<?php

/**
 * @package redaxo4
 * @version svn:$Id$
 */

ob_start();
ob_implicit_flush(0);

// Globale Variablen vorbereiten

require 'redaxo/include/functions/function_rex_mquotes.inc.php';

// $REX vorbereiten

unset($REX);
$REX['REDAXO']      = false; // Backend = true, Frontend = false
$REX['GG']          = true;  // Get from Generated?
$REX['HTDOCS_PATH'] = './';

// Core laden

require_once 'redaxo/include/master.inc.php';
require_once 'redaxo/include/addons.inc.php';

// Setup?

if ($REX['SETUP']) {
	header('Location: redaxo/');
	exit();
}

// Aktuellen Artikel finden und ausgeben

$REX['ARTICLE'] = new rex_article();
$REX['ARTICLE']->setCLang($REX['CUR_CLANG']);

if ($REX['SETUP']) {
	header('Location: redaxo/index.php');
	exit();
}
elseif ($REX['ARTICLE']->setArticleId($REX['ARTICLE_ID'])) {
	print $REX['ARTICLE']->getArticleTemplate();
}
else {
	print 'Kein Startartikel selektiert. Bitte setze ihn im <a href="redaxo/index.php">Backend</a>.';
	$REX['STATS'] = 0;
}

$content = ob_get_clean();
rex_send_article($REX['ARTICLE'], $content, 'frontend');
