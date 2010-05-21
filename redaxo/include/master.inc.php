<?php

// wir gehen davon aus, dass $SLY['HTDOCS_PATH'] existiert. Das ist
// eine Annahme die den code hier schneller macht und vertretbar ist
// wer das falsch setzt hat es verdient, dass das script nicht läuft

$SLY['FRONTEND_PATH'] = realpath($SLY['HTDOCS_PATH']);
$SLY['INCLUDE_PATH']  = $SLY['FRONTEND_PATH'].DIRECTORY_SEPARATOR.'redaxo'.DIRECTORY_SEPARATOR.'include';
$SLY['DATAFOLDER']    = $SLY['FRONTEND_PATH'].DIRECTORY_SEPARATOR.'data';
$SLY['MEDIAFOLDER']   = $SLY['DATAFOLDER'].DIRECTORY_SEPARATOR.'mediapool';
$SLY['DYNFOLDER']     = $SLY['DATAFOLDER'].DIRECTORY_SEPARATOR.'dyn';

// Loader initialisieren

if (empty($SLY['NOFUNCTIONS'])) {
	require_once $SLY['INCLUDE_PATH'].'/loader.php';
}

// Kernkonfiguration laden

$config = sly_Core::config();
$SLY    = array_merge($SLY, $config->get(null));

$config->appendArray($SLY);

// Sync?

if (empty($SLY['SYNC'])){
	// ----- standard variables
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
		$SLY['CLANG'][$clang->getId()] = $clang->getName();
	}
	unset($clangs);


  	$SLY['CUR_CLANG']  = sly_Core::getCurrentClang();
	$SLY['ARTICLE_ID'] = sly_Core::getCurrentArticleId();
}
