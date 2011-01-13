<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

// Magic Quotes entfernen, wenn vorhanden

if (get_magic_quotes_gpc()) {
	function stripslashes_ref(&$value) {
		$value = stripslashes($value);
	}

	array_walk_recursive($_GET,     'stripslashes_ref');
	array_walk_recursive($_POST,    'stripslashes_ref');
	array_walk_recursive($_COOKIE,  'stripslashes_ref');
	array_walk_recursive($_REQUEST, 'stripslashes_ref');
}

// Register Globals entfernen

if (ini_get('register_globals')) {
	$superglobals = array('REX', '_GET', '_POST', '_REQUEST', '_ENV', '_FILES', '_SESSION', '_COOKIE', '_SERVER');
	$keys         = array_keys($GLOBALS);

	foreach ($keys as $key) {
		if (!in_array($key, $superglobals) && $key != 'GLOBALS') {
			unset($$key);
		}
	}

	unset($superglobals, $key, $keys);
}

// So, jetzt haben wir eine saubere Grundlage für unsere Aufgaben.

// Wir gehen davon aus, dass $REX['HTDOCS_PATH'] existiert. Das ist
// eine Annahme die den Code hier schneller macht und vertretbar ist.
// Wer das falsch setzt, hat es verdient, dass das Script nicht läuft.

$REX['FRONTEND_PATH'] = realpath($REX['HTDOCS_PATH']);
$REX['INCLUDE_PATH']  = $REX['FRONTEND_PATH'].DIRECTORY_SEPARATOR.'redaxo'.DIRECTORY_SEPARATOR.'include';
$REX['DATAFOLDER']    = $REX['FRONTEND_PATH'].DIRECTORY_SEPARATOR.'data';
$REX['MEDIAFOLDER']   = $REX['DATAFOLDER'].DIRECTORY_SEPARATOR.'mediapool';
$REX['DYNFOLDER']     = $REX['DATAFOLDER'].DIRECTORY_SEPARATOR.'dyn';

define('SLY_BASE',         $REX['FRONTEND_PATH']);
define('SLY_INCLUDE_PATH', $REX['INCLUDE_PATH']);
define('SLY_DYNFOLDER',    $REX['DYNFOLDER']);

// Loader initialisieren

if (empty($REX['NOFUNCTIONS'])) {
	require_once $REX['INCLUDE_PATH'].'/loader.php';
}

// Kernkonfiguration laden

$config = sly_Core::config();
$config->loadStatic($REX['INCLUDE_PATH'].'/config/sallyStatic.yml');
$config->loadLocalConfig();
$config->loadLocalDefaults($REX['INCLUDE_PATH'].'/config/sallyDefaults.yml');

// nicht unbedingt die beste Lösung, aber praktikabel
try {
	$config->loadProjectConfig();
}
catch (Exception $e) {
	// geht halt nicht
}

// Sync?
if (empty($REX['SYNC']) && !$config->get('SETUP')){
	// Standard-Variablen
	sly_Core::registerVarType('rex_var_globals');
	sly_Core::registerVarType('rex_var_article');
	sly_Core::registerVarType('rex_var_category');
	sly_Core::registerVarType('rex_var_template');
	sly_Core::registerVarType('rex_var_value');
	sly_Core::registerVarType('rex_var_link');
	sly_Core::registerVarType('rex_var_media');

	// Sprachen laden
	$clangs = sly_Service_Factory::getService('Language')->find(null, null, 'id');

	foreach($clangs as $clang){
		$REX['CLANG'][$clang->getId()] = $clang->getName();
	}

	unset($clangs);

  	$REX['CUR_CLANG']  = sly_Core::getCurrentClang();
	$REX['ARTICLE_ID'] = sly_Core::getCurrentArticleId();
}

// REDAXO compatibility
if (!$config->has('TABLE_PREFIX')) $config->setLocal('TABLE_PREFIX', $config->get('DATABASE/TABLE_PREFIX'));
$REX = array_merge($REX, $config->get(null));
