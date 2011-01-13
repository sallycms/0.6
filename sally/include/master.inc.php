<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
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
$REX['INCLUDE_PATH']  = $REX['FRONTEND_PATH'].DIRECTORY_SEPARATOR.'sally'.DIRECTORY_SEPARATOR.'include';
$REX['DATAFOLDER']    = $REX['FRONTEND_PATH'].DIRECTORY_SEPARATOR.'data';
$REX['MEDIAFOLDER']   = $REX['DATAFOLDER'].DIRECTORY_SEPARATOR.'mediapool';
$REX['DYNFOLDER']     = $REX['DATAFOLDER'].DIRECTORY_SEPARATOR.'dyn';

define('SLY_BASE',         $REX['FRONTEND_PATH']);
define('SLY_INCLUDE_PATH', $REX['INCLUDE_PATH']);
define('SLY_DYNFOLDER',    $REX['DYNFOLDER']);
define('SLY_DATAFOLDER',   $REX['DATAFOLDER']);
define('SLY_MEDIAFOLDER',  $REX['MEDIAFOLDER']);
define('SLY_DEVELOPFOLDER', SLY_BASE.DIRECTORY_SEPARATOR.'develop');

// Loader initialisieren

if (empty($REX['NOFUNCTIONS'])) {
	require_once SLY_INCLUDE_PATH.'/loader.php';
}

// Kernkonfiguration laden

$config = sly_Core::config();
$config->loadStatic(SLY_INCLUDE_PATH.'/config/sallyStatic.yml');
$config->loadLocalConfig();
$config->loadLocalDefaults(SLY_INCLUDE_PATH.'/config/sallyDefaults.yml');
$config->loadProjectConfig();

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
	$REX['CLANG'] = sly_Core::cache()->get('sly.language', 'all', null);

	if (!is_array($REX['CLANG'])) {
		$REX['CLANG'] = array();
		$clangs       = sly_Service_Factory::getService('Language')->find(null, null, 'id');

		foreach ($clangs as $clang) {
			$REX['CLANG'][$clang->getId()] = $clang->getName();
		}

		sly_Core::cache()->set('sly.language', 'all', $REX['CLANG']);
	}

	$REX['CUR_CLANG']  = sly_Core::getCurrentClang();
	$REX['ARTICLE_ID'] = sly_Core::getCurrentArticleId();
}

// REDAXO compatibility

if (!$config->has('TABLE_PREFIX')) $config->setLocal('TABLE_PREFIX', $config->get('DATABASE/TABLE_PREFIX'));
$REX = array_merge($REX, $config->get(null));

// Check for system updates

$coreVersion  = sly_Core::getVersion('X.Y.Z');
$knownVersion = sly_Util_Versions::get('sally');

if ($knownVersion !== $coreVersion) {
	// dummy: implement some clever update mechanism (if needed)
	sly_Util_Versions::set('sally', $coreVersion);
}
