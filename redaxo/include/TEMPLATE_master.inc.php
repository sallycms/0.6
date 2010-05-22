<?php

// versch. Pfade (nicht realpath() anwenden, da nicht alle Verzeichnisse immer existieren)

$SLY['INCLUDE_PATH']  = preg_replace('#[/\\\\][/\\\\]#i', DIRECTORY_SEPARATOR, $SLY['HTDOCS_PATH'].'redaxo/include');
$SLY['FRONTEND_PATH'] = preg_replace('#[/\\\\][/\\\\]#i', DIRECTORY_SEPARATOR, $SLY['HTDOCS_PATH']);
$SLY['DATAFOLDER']    = preg_replace('#[/\\\\][/\\\\]#i', DIRECTORY_SEPARATOR, $SLY['HTDOCS_PATH'].'data');
$SLY['MEDIAFOLDER']   = preg_replace('#[/\\\\][/\\\\]#i', DIRECTORY_SEPARATOR, $SLY['HTDOCS_PATH'].'data/mediapool');
$SLY['DYNFOLDER']     = preg_replace('#[/\\\\][/\\\\]#i', DIRECTORY_SEPARATOR, $SLY['HTDOCS_PATH'].'data/dyn');

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
	$config->appendFile($SLY['INCLUDE_PATH'].'/config/clang.yaml', 'CLANG');
	$SLY['CLANG'] = $config->get('CLANG');
	
	$SLY['CUR_CLANG']  = sly_Core::getCurrentClang();
	$SLY['ARTICLE_ID'] = sly_Core::getCurrentArticleId();
}
