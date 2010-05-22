<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

require_once $SLY['INCLUDE_PATH'].'/lib/sly/Loader.php';

sly_Loader::addLoadPath($SLY['INCLUDE_PATH'].'/lib');
sly_Loader::addLoadPath($SLY['INCLUDE_PATH'].'/lib/sfYaml');
sly_Loader::addLoadPath($SLY['INCLUDE_PATH'].'/controllers', 'sly_Controller_');
sly_Loader::addLoadPath($REX['INCLUDE_PATH'].'/layout', 'sly_Layout_');
sly_Loader::addLoadPath($SLY['INCLUDE_PATH'].'/lib/rex/oo', 'OO');
sly_Loader::register();

require_once $REX['INCLUDE_PATH'].'/lib/functions.php';

// Funktionen

require_once $REX['INCLUDE_PATH'].'/functions/function_rex_globals.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_client_cache.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_url.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_extension.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_addons.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_plugins.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_other.inc.php';

if ($REX['REDAXO']) {
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_time.inc.php';
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_title.inc.php';
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_generate.inc.php';
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_mediapool.inc.php';
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_structure.inc.php';
}
