<?php



// wir gehen davon aus, dass $REX['HTDOCS_PATH'] existiert. Das ist
// eine Annahme die den code hier schneller macht und vertretbar ist
// wer das falsch setzt hat es verdient, dass das script nicht läuft

$REX['FRONTEND_PATH'] = realpath($REX['HTDOCS_PATH']);
$REX['INCLUDE_PATH']  = $REX['FRONTEND_PATH'].DIRECTORY_SEPARATOR.'redaxo'.DIRECTORY_SEPARATOR.'include';
$REX['DATAFOLDER']    = $REX['FRONTEND_PATH'].DIRECTORY_SEPARATOR.'data';
$REX['MEDIAFOLDER']   = $REX['DATAFOLDER'].DIRECTORY_SEPARATOR.'mediapool';
$REX['DYNFOLDER']     = $REX['DATAFOLDER'].DIRECTORY_SEPARATOR.'dyn';

define('SLY_INCLUDE_PATH', $REX['INCLUDE_PATH']);
define('SLY_DYNFOLDER',    $REX['DYNFOLDER']);

// Loader initialisieren

if (empty($REX['NOFUNCTIONS'])) {
	require_once $REX['INCLUDE_PATH'].'/loader.php';
}

// Kernkonfiguration laden

$config = sly_Core::config();
$config->loadStatic($REX['INCLUDE_PATH'].'/config/sallyStatic.yaml');
$config->loadLocalDefaults($REX['INCLUDE_PATH'].'/config/sallyDefaults.yaml');
$config->loadLocalConfig();
$config->loadProjectConfig();
$config->set('/', $REX, sly_Configuration::STORE_TEMP);
$REX = $config;
// Sync?

if (empty($REX['SYNC'])){
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
		$REX['CLANG'][$clang->getId()] = $clang->getName();
	}
	unset($clangs);

  	$REX['CUR_CLANG']  = sly_Core::getCurrentClang();
	$REX['ARTICLE_ID'] = sly_Core::getCurrentArticleId();
}
